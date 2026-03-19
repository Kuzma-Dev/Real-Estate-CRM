use thiserror::Error;

#[derive(Error, Debug)]
pub enum DownloadError {
    #[error("HTTP request failed: {0}")]
    RequestError(#[from] reqwest::Error),
    
    #[error("I/O error: {0}")]
    IoError(#[from] std::io::Error),
    
    #[error("URL parsing error: {0}")]
    UrlError(#[from] url::ParseError),
    
    #[error("Download timeout after {0} seconds")]
    Timeout(u64),
    
    #[error("Maximum retries ({0}) exceeded")]
    MaxRetriesExceeded(usize),
    
    #[error("Download queue is full")]
    QueueFull,
    
    #[error("Invalid response: {0}")]
    InvalidResponse(String),
    
    #[error("Semaphore closed")]
    SemaphoreClosed,
    
    #[error("Task cancelled")]
    TaskCancelled,
    
    #[error("Backpressure limit reached")]
    BackpressureLimit,
}
