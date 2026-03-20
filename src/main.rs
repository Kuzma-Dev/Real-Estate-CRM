use anyhow::Result;
use async_download_manager::{DownloadManager, DownloadConfig, DownloadRequest};
use clap::{Parser, ValueEnum};
use std::path::PathBuf;
use tracing::{info, error, warn};
use tracing_subscriber;
use serde::Deserialize;
use tokio::fs;

#[derive(Parser, Debug)]
#[command(author, version, about, long_about = None)]
struct Args {
    /// URLs to download (can be specified multiple times or via --file)
    #[arg(short, long)]
    urls: Vec<String>,

    /// File containing URLs (one per line)
    #[arg(short, long)]
    file: Option<PathBuf>,

    /// Output directory for downloaded files
    #[arg(short, long, default_value = "downloads")]
    output: PathBuf,

    /// Maximum concurrent downloads
    #[arg(long, default_value = "10")]
    max_concurrent: usize,

    /// Maximum retry attempts per download
    #[arg(long, default_value = "3")]
    max_retries: usize,

    /// Base delay for exponential backoff (milliseconds)
    #[arg(long, default_value = "1000")]
    base_delay: u64,

    /// Maximum delay for exponential backoff (milliseconds)
    #[arg(long, default_value = "30000")]
    max_delay: u64,

    /// Download timeout per file (seconds)
    #[arg(long, default_value = "30")]
    timeout: u64,

    /// Chunk size for streaming downloads (bytes)
    #[arg(long, default_value = "8192")]
    chunk_size: usize,

    /// Enable adaptive buffer sizing based on network throughput
    #[arg(long)]
    adaptive_buffering: bool,

    /// Minimum buffer size for adaptive buffering (bytes)
    #[arg(long, default_value = "4096")]
    min_buffer_size: usize,

    /// Maximum buffer size for adaptive buffering (bytes)
    #[arg(long, default_value = "65536")]
    max_buffer_size: usize,

    /// Log level
    #[arg(long, value_enum, default_value = "info")]
    log_level: LogLevel,

    /// Custom user agent string
    #[arg(long, default_value = "async-download-manager/0.1.0")]
    user_agent: String,

    /// Enable detailed progress reporting
    #[arg(long)]
    verbose: bool,
}

#[derive(Debug, Clone, ValueEnum)]
enum LogLevel {
    Trace,
    Debug,
    Info,
    Warn,
    Error,
}

impl From<LogLevel> for tracing::Level {
    fn from(level: LogLevel) -> Self {
        match level {
            LogLevel::Trace => tracing::Level::TRACE,
            LogLevel::Debug => tracing::Level::DEBUG,
            LogLevel::Info => tracing::Level::INFO,
            LogLevel::Warn => tracing::Level::WARN,
            LogLevel::Error => tracing::Level::ERROR,
        }
    }
}

#[derive(Debug, Deserialize)]
struct UrlEntry {
    url: String,
    filename: Option<String>,
    priority: Option<u8>,
}

#[tokio::main]
async fn main() -> Result<()> {
    let args = Args::parse();

    let filter = tracing_subscriber::EnvFilter::try_from_default_env()
        .unwrap_or_else(|_| {
            tracing_subscriber::EnvFilter::new(format!(
                "async_download_manager={}",
                match args.log_level {
                    LogLevel::Trace => "trace",
                    LogLevel::Debug => "debug",
                    LogLevel::Info => "info",
                    LogLevel::Warn => "warn",
                    LogLevel::Error => "error",
                }
            ))
        });

    tracing_subscriber::fmt()
        .with_env_filter(filter)
        .with_target(false)
        .with_thread_ids(args.verbose)
        .with_file(args.verbose)
        .with_line_number(args.verbose)
        .init();

    info!("🚀 Async Download Manager starting...");
    info!("Configuration: max_concurrent={}, max_retries={}, timeout={}s", 
          args.max_concurrent, args.max_retries, args.timeout);

    let config = DownloadConfig {
        max_concurrent_downloads: args.max_concurrent,
        max_retries: args.max_retries,
        base_delay_ms: args.base_delay,
        max_delay_ms: args.max_delay,
        chunk_size: args.chunk_size,
        timeout_seconds: args.timeout,
        user_agent: args.user_agent,
        adaptive_buffering: args.adaptive_buffering,
        min_buffer_size: args.min_buffer_size,
        max_buffer_size: args.max_buffer_size,
    };

    let manager = DownloadManager::new(config)?;
    fs::create_dir_all(&args.output).await?;

    let mut requests = Vec::new();

    if let Some(file_path) = &args.file {
        info!("📁 Loading URLs from file: {}", file_path.display());
        let content = fs::read_to_string(file_path).await?;
        
        for line in content.lines() {
            let line = line.trim();
            if line.is_empty() || line.starts_with('#') {
                continue;
            }

            if let Ok(entry) = serde_json::from_str::<UrlEntry>(line) {
                let filename = entry.filename.unwrap_or_else(|| {
                    extract_filename_from_url(&entry.url).unwrap_or_else(|| "download.bin".to_string())
                });
                let output_path = args.output.join(filename);
                let mut request = DownloadRequest::new(entry.url, output_path);
                if let Some(priority) = entry.priority {
                    request = request.with_priority(priority);
                }
                requests.push(request);
            } else {
                let filename = extract_filename_from_url(line)
                    .unwrap_or_else(|| format!("download_{}.bin", requests.len()));
                let output_path = args.output.join(filename);
                requests.push(DownloadRequest::new(line.to_string(), output_path));
            }
        }
    } else {
        for url in &args.urls {
            let filename = extract_filename_from_url(url)
                .unwrap_or_else(|| format!("download_{}.bin", requests.len()));
            let output_path = args.output.join(filename);
            requests.push(DownloadRequest::new(url.clone(), output_path));
        }
    }

    if requests.is_empty() {
        error!("❌ No URLs to download. Provide URLs via --urls or --file");
        return Ok(());
    }

    info!("📋 {} download(s) queued", requests.len());

    let start_time = std::time::Instant::now();

    for request in &requests {
        manager.submit_download(request.clone()).await?;
    }

    // Use graceful shutdown processing
    let results = manager.process_queue_with_graceful_shutdown().await;

    let duration = start_time.elapsed();
    let successful = results.iter().filter(|r| r.success).count();
    let failed = results.len() - successful;
    let total_bytes: u64 = results.iter()
        .filter(|r| r.success)
        .map(|r| r.bytes_downloaded)
        .sum();

    info!("📊 Download Summary:");
    info!("  ✅ Successful: {}", successful);
    info!("  ❌ Failed: {}", failed);
    info!("  📦 Total bytes: {} ({:.2} MB)", total_bytes, total_bytes as f64 / 1024.0 / 1024.0);
    info!("  ⏱️  Duration: {:.2}s", duration.as_secs_f64());

    if failed > 0 {
        warn!("Failed downloads:");
        for result in results.iter().filter(|r| !r.success) {
            if let Some(error) = &result.error {
                warn!("  ❌ {}: {}", result.request.url, error);
            }
        }
    }

    info!("🏁 Async Download Manager finished");

    Ok(())
}

fn extract_filename_from_url(url: &str) -> Option<String> {
    let parsed = url::Url::parse(url).ok()?;
    let path = parsed.path();
    let filename = path.split('/').last()?;
    
    if filename.is_empty() || filename == "." || filename == ".." {
        return None;
    }

    if filename.contains('.') {
        Some(filename.to_string())
    } else {
        Some(format!("{}.bin", filename))
    }
}
