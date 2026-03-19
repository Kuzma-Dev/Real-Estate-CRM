use crate::{DownloadConfig, DownloadRequest, DownloadResult, DownloadError};
use crate::download_task::DownloadTask;
use tokio::sync::{mpsc, Semaphore};
use tokio::time::{timeout, Duration};
use tracing::{debug, error, info, warn};
use anyhow::{Context, Result};
use std::path::PathBuf;
use std::sync::Arc;
use futures::stream::{StreamExt, FuturesUnordered};
use std::collections::BinaryHeap;
use std::cmp::Ordering;

#[derive(Debug)]
struct PriorityTask {
    request: DownloadRequest,
    priority: u8,
}

impl PartialEq for PriorityTask {
    fn eq(&self, other: &Self) -> bool {
        self.priority == other.priority
    }
}

impl Eq for PriorityTask {}

impl PartialOrd for PriorityTask {
    fn partial_cmp(&self, other: &Self) -> Option<Ordering> {
        Some(self.cmp(other))
    }
}

impl Ord for PriorityTask {
    fn cmp(&self, other: &Self) -> Ordering {
        other.priority.cmp(&self.priority)
    }
}

pub struct DownloadManager {
    config: Arc<DownloadConfig>,
    semaphore: Arc<Semaphore>,
    task_sender: mpsc::UnboundedSender<DownloadRequest>,
    task_receiver: Arc<tokio::sync::Mutex<mpsc::UnboundedReceiver<DownloadRequest>>>,
    client: reqwest::Client,
}

impl DownloadManager {
    pub fn new(config: DownloadConfig) -> Result<Self> {
        let client = reqwest::Client::builder()
            .timeout(Duration::from_secs(config.timeout_seconds))
            .user_agent(&config.user_agent)
            .build()
            .context("Failed to create HTTP client")?;

        let (task_sender, task_receiver) = mpsc::unbounded_channel();
        let semaphore = Arc::new(Semaphore::new(config.max_concurrent_downloads));

        Ok(Self {
            config: Arc::new(config),
            semaphore,
            task_sender,
            task_receiver: Arc::new(tokio::sync::Mutex::new(task_receiver)),
            client,
        })
    }

    pub async fn submit_download(&self, request: DownloadRequest) -> Result<()> {
        self.task_sender.send(request)
            .map_err(|_| DownloadError::QueueFull)?;
        Ok(())
    }

    pub async fn submit_downloads_batch(&self, requests: Vec<DownloadRequest>) -> Result<usize> {
        let mut submitted = 0;
        for request in requests {
            match self.submit_download(request).await {
                Ok(()) => submitted += 1,
                Err(e) => {
                    error!("Failed to submit download request: {}", e);
                    break;
                }
            }
        }
        Ok(submitted)
    }

    pub async fn process_queue(&self) -> Vec<DownloadResult> {
        let mut results = Vec::new();
        let mut active_downloads = FuturesUnordered::new();
        let mut receiver = self.task_receiver.lock().await;

        loop {
            tokio::select! {
                Some(request) = receiver.recv() => {
                    if let Ok(permit) = self.semaphore.clone().try_acquire_owned() {
                        let task = DownloadTask::new(
                            request,
                            self.client.clone(),
                            (*self.config).clone(),
                            permit,
                        );
                        active_downloads.push(tokio::spawn(task.execute()));
                    } else {
                        warn!("Download queue at capacity, waiting for slot...");
                        if let Ok(permit) = self.semaphore.clone().acquire_owned().await {
                            let task = DownloadTask::new(
                                request,
                                self.client.clone(),
                                (*self.config).clone(),
                                permit,
                            );
                            active_downloads.push(tokio::spawn(task.execute()));
                        }
                    }
                }
                Some(result) = active_downloads.next() => {
                    match result {
                        Ok(download_result) => {
                            info!("Download completed: {:?}", download_result);
                            results.push(download_result);
                        }
                        Err(join_error) => {
                            error!("Download task failed: {}", join_error);
                        }
                    }
                }
                else => {
                    break;
                }
            }

            if active_downloads.is_empty() && receiver.is_empty() {
                break;
            }
        }

        while let Some(result) = active_downloads.next().await {
            match result {
                Ok(download_result) => {
                    info!("Final download completed: {:?}", download_result);
                    results.push(download_result);
                }
                Err(join_error) => {
                    error!("Final download task failed: {}", join_error);
                }
            }
        }

        results
    }

    pub async fn download_with_result(&self, request: DownloadRequest) -> Result<DownloadResult> {
        let permit = self.semaphore.clone().acquire_owned().await
            .map_err(|_| DownloadError::SemaphoreClosed)?;

        let task = DownloadTask::new(request, self.client.clone(), (*self.config).clone(), permit);
        Ok(task.execute().await)
    }

    pub async fn shutdown(&self) -> Result<()> {
        info!("Shutting down download manager...");
        
        drop(self.task_sender.clone());
        
        let remaining_tasks = self.semaphore.available_permits();
        if remaining_tasks < self.config.max_concurrent_downloads {
            warn!("Waiting for {} active downloads to complete...", 
                  self.config.max_concurrent_downloads - remaining_tasks);
        }

        Ok(())
    }

    pub fn get_stats(&self) -> ManagerStats {
        ManagerStats {
            max_concurrent: self.config.max_concurrent_downloads,
            active_downloads: self.config.max_concurrent_downloads - self.semaphore.available_permits(),
            available_slots: self.semaphore.available_permits(),
        }
    }
}

#[derive(Debug, Clone)]
pub struct ManagerStats {
    pub max_concurrent: usize,
    pub active_downloads: usize,
    pub available_slots: usize,
}

#[cfg(test)]
mod tests {
    use super::*;
    use tempfile::TempDir;
    use tokio::fs;

    #[tokio::test]
    async fn test_download_manager_creation() {
        let config = DownloadConfig::default();
        let manager = DownloadManager::new(config).unwrap();
        let stats = manager.get_stats();
        assert_eq!(stats.active_downloads, 0);
        assert_eq!(stats.available_slots, 10);
    }

    #[tokio::test]
    async fn test_single_download() -> Result<()> {
        let temp_dir = TempDir::new()?;
        let config = DownloadConfig {
            max_concurrent_downloads: 1,
            ..Default::default()
        };
        
        let manager = DownloadManager::new(config)?;
        let request = DownloadRequest::new(
            "https://httpbin.org/bytes/1024".to_string(),
            temp_dir.path().join("test.bin"),
        );

        let result = manager.download_with_result(request).await?;
        assert!(result.success);
        assert!(result.bytes_downloaded > 0);
        
        Ok(())
    }
}
