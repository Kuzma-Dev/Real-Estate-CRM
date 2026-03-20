use anyhow::Context;
use crate::{DownloadConfig, DownloadRequest, DownloadResult, DownloadError};
use tokio::sync::OwnedSemaphorePermit;
use tokio::time::{sleep, Duration, Instant};
use tokio::io::AsyncWriteExt;
use tracing::{debug, error, info, warn};
use futures::StreamExt;
use std::sync::Arc;
use std::path::PathBuf;

pub struct DownloadTask {
    request: DownloadRequest,
    client: reqwest::Client,
    config: DownloadConfig,
    _permit: OwnedSemaphorePermit,
}

impl DownloadTask {
    pub fn new(
        request: DownloadRequest,
        client: reqwest::Client,
        config: DownloadConfig,
        permit: OwnedSemaphorePermit,
    ) -> Self {
        Self {
            request,
            client,
            config,
            _permit: permit,
        }
    }

    pub async fn execute(self) -> DownloadResult {
        let start_time = Instant::now();
        let mut attempt = 0;
        let mut last_error = String::new();

        while attempt <= self.config.max_retries {
            attempt += 1;
            debug!("Download attempt {} for URL: {}", attempt, self.request.url);

            match self.download_single_attempt().await {
                Ok(bytes_downloaded) => {
                    let duration = start_time.elapsed().as_millis() as u64;
                    info!(
                        "Download successful: {} ({} bytes, {}ms, {} attempts)",
                        self.request.url,
                        bytes_downloaded,
                        duration,
                        attempt
                    );
                    return DownloadResult::success(self.request, bytes_downloaded, duration);
                }
                Err(e) => {
                    last_error = e.to_string();
                    warn!(
                        "Download attempt {} failed for URL: {} - {}",
                        attempt, self.request.url, e
                    );

                    if attempt <= self.config.max_retries {
                        let delay = self.calculate_backoff_delay(attempt);
                        debug!("Waiting {}ms before retry...", delay);
                        sleep(Duration::from_millis(delay)).await;
                    }
                }
            }
        }

        error!(
            "Download failed after {} attempts: {} - {}",
            self.config.max_retries + 1,
            self.request.url,
            last_error
        );

        DownloadResult::failure(self.request, last_error)
    }

    async fn download_single_attempt(&self) -> Result<u64, DownloadError> {
        let response = self.client
            .get(&self.request.url)
            .send()
            .await
            .map_err(|e| DownloadError::RequestError(e))?;

        if !response.status().is_success() {
            return Err(DownloadError::InvalidResponse(
                format!("HTTP status: {}", response.status())
            ));
        }

        let content_length = response
            .content_length()
            .unwrap_or(0);

        debug!("Starting download, content length: {} bytes", content_length);

        if let Some(parent) = self.request.output_path.parent() {
            tokio::fs::create_dir_all(parent).await
                .map_err(|e| DownloadError::IoError(e))?;
        }

        let mut file = tokio::fs::File::create(&self.request.output_path).await
            .map_err(|e| DownloadError::IoError(e))?;

        let mut downloaded_bytes = 0u64;
        let mut stream = response.bytes_stream();

        // Adaptive buffer sizing based on network performance
        let mut buffer_size = self.config.chunk_size;
        let mut last_throughput_sample = std::time::Instant::now();
        let mut bytes_since_sample = 0u64;
        const SAMPLE_INTERVAL: std::time::Duration = std::time::Duration::from_secs(1);

        while let Some(chunk_result) = stream.next().await {
            let chunk: bytes::Bytes = chunk_result.map_err(|e| DownloadError::RequestError(e))?;
            
            // Use adaptive buffer size if enabled
            if self.config.adaptive_buffering {
                self.calculate_adaptive_buffer_size(chunk.len(), &mut buffer_size, &mut bytes_since_sample, &mut last_throughput_sample, SAMPLE_INTERVAL);
            }

            file.write_all(&chunk).await
                .map_err(|e| DownloadError::IoError(e))?;
            
            downloaded_bytes += chunk.len() as u64;

            if content_length > 0 {
                let progress = (downloaded_bytes as f64 / content_length as f64) * 100.0;
                debug!("Download progress: {:.1}% ({} / {} bytes), buffer: {}B", 
                       progress, downloaded_bytes, content_length, buffer_size);
            }
        }

        file.flush().await
            .map_err(|e| DownloadError::IoError(e))?;

        if content_length > 0 && downloaded_bytes != content_length {
            return Err(DownloadError::InvalidResponse(
                format!("Incomplete download: expected {} bytes, got {} bytes",
                       content_length, downloaded_bytes)
            ));
        }

        Ok(downloaded_bytes)
    }

    fn calculate_adaptive_buffer_size(&self, chunk_size: usize, current_buffer: &mut usize, bytes_since_sample: &mut u64, last_sample: &mut std::time::Instant, sample_interval: std::time::Duration) {
        *bytes_since_sample += chunk_size as u64;
        
        // Sample throughput every second
        if last_sample.elapsed() >= sample_interval {
            let throughput = *bytes_since_sample as f64 / last_sample.elapsed().as_secs_f64();
            
            // Adjust buffer size based on throughput
            if throughput > 10_000.0 * 1024.0 { // > 10 MB/s
                *current_buffer = std::cmp::min(*current_buffer * 2, self.config.max_buffer_size);
            } else if throughput < 1_000.0 * 1024.0 { // < 1 MB/s
                *current_buffer = std::cmp::max(*current_buffer / 2, self.config.min_buffer_size);
            }
            
            debug!("Throughput: {:.2} MB/s, adjusted buffer to: {} bytes", 
                   throughput / (1024.0 * 1024.0), *current_buffer);
            
            *bytes_since_sample = 0;
            *last_sample = std::time::Instant::now();
        }
    }

    fn calculate_backoff_delay(&self, attempt: usize) -> u64 {
        let base_delay = self.config.base_delay_ms;
        let max_delay = self.config.max_delay_ms;
        
        // Exponential backoff: delay = base_delay * 2^(attempt-1)
        let exponential_delay = base_delay * (2_u64.pow(attempt.saturating_sub(1) as u32));
        
        // Add jitter to prevent thundering herd (±25% of base delay)
        let jitter_range = base_delay / 4; // 25% of base delay
        let jitter = if jitter_range > 0 {
            fastrand::u64(0..=jitter_range)
        } else {
            0
        };
        
        // Add additional jitter for larger delays to prevent synchronized retries
        let additional_jitter = if exponential_delay > 5000 {
            fastrand::u64(0..=(exponential_delay / 20)) // 5% of exponential delay
        } else {
            0
        };
        
        let total_delay = exponential_delay + jitter + additional_jitter;
        
        // Cap at maximum delay
        std::cmp::min(total_delay, max_delay)
    }

    async fn verify_download(&self, expected_size: Option<u64>) -> Result<(), DownloadError> {
        let metadata = tokio::fs::metadata(&self.request.output_path).await
            .map_err(|e| DownloadError::IoError(e))?;

        let actual_size = metadata.len();

        if let Some(expected) = expected_size {
            if actual_size != expected {
                return Err(DownloadError::InvalidResponse(
                    format!("Size mismatch: expected {} bytes, got {} bytes",
                           expected, actual_size)
                ));
            }
        }

        if actual_size == 0 {
            return Err(DownloadError::InvalidResponse(
                "Downloaded file is empty".to_string()
            ));
        }

        Ok(())
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::DownloadConfig;
    use tempfile::TempDir;
    use tokio::sync::Semaphore;

    #[tokio::test]
    async fn test_backoff_delay_calculation() {
        let config = DownloadConfig {
            base_delay_ms: 1000,
            max_delay_ms: 10000,
            ..Default::default()
        };

        let task = DownloadTask {
            request: DownloadRequest::new(
                "https://example.com".to_string(),
                PathBuf::from("/tmp/test"),
            ),
            client: reqwest::Client::new(),
            config,
            _permit: Arc::new(Semaphore::new(1)).acquire_owned().await.unwrap(),
        };

        let delay1 = task.calculate_backoff_delay(1);
        let delay2 = task.calculate_backoff_delay(2);
        let delay3 = task.calculate_backoff_delay(3);

        assert!(delay1 < delay2);
        assert!(delay2 < delay3);
        assert!(delay3 <= 10000);
    }

    #[tokio::test]
    async fn test_download_with_retry() -> Result<(), Box<dyn std::error::Error>> {
        let temp_dir = TempDir::new()?;
        let config = DownloadConfig {
            max_retries: 2,
            ..Default::default()
        };

        let request = DownloadRequest::new(
            "https://httpbin.org/status/500".to_string(),
            temp_dir.path().join("test.txt"),
        );

        let client = reqwest::Client::new();
        let permit = Arc::new(Semaphore::new(1)).acquire_owned().await.unwrap();
        
        let task = DownloadTask::new(request, client, config, permit);
        let result = task.execute().await;

        assert!(!result.success);
        assert!(result.error.is_some());

        Ok(())
    }
}
