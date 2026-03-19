use anyhow::{Context, Result};
use std::path::PathBuf;
use tracing::{error, info};
use uuid::Uuid;

pub mod download_task;
pub mod manager;
pub mod error;

pub use download_task::DownloadTask;
pub use manager::DownloadManager;
pub use error::DownloadError;

#[derive(Debug, Clone)]
pub struct DownloadConfig {
    pub max_concurrent_downloads: usize,
    pub max_retries: usize,
    pub base_delay_ms: u64,
    pub max_delay_ms: u64,
    pub chunk_size: usize,
    pub timeout_seconds: u64,
    pub user_agent: String,
}

impl Default for DownloadConfig {
    fn default() -> Self {
        Self {
            max_concurrent_downloads: 10,
            max_retries: 3,
            base_delay_ms: 1000,
            max_delay_ms: 30000,
            chunk_size: 8192,
            timeout_seconds: 30,
            user_agent: "async-download-manager/0.1.0".to_string(),
        }
    }
}

#[derive(Debug, Clone)]
pub struct DownloadRequest {
    pub id: Uuid,
    pub url: String,
    pub output_path: PathBuf,
    pub priority: u8,
}

impl DownloadRequest {
    pub fn new(url: String, output_path: PathBuf) -> Self {
        Self {
            id: Uuid::new_v4(),
            url,
            output_path,
            priority: 0,
        }
    }

    pub fn with_priority(mut self, priority: u8) -> Self {
        self.priority = priority;
        self
    }
}

#[derive(Debug, Clone)]
pub struct DownloadResult {
    pub request: DownloadRequest,
    pub success: bool,
    pub bytes_downloaded: u64,
    pub duration_ms: u64,
    pub error: Option<String>,
}

impl DownloadResult {
    pub fn success(request: DownloadRequest, bytes_downloaded: u64, duration_ms: u64) -> Self {
        Self {
            request,
            success: true,
            bytes_downloaded,
            duration_ms,
            error: None,
        }
    }

    pub fn failure(request: DownloadRequest, error: String) -> Self {
        Self {
            request,
            success: false,
            bytes_downloaded: 0,
            duration_ms: 0,
            error: Some(error),
        }
    }
}
