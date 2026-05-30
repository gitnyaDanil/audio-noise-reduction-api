# 🎧 AI Audio Noise Reduction API

An API-driven background noise reduction service for short-form video content, powered by **DeepFilterNet3** — a state-of-the-art neural network for speech enhancement. Built with Laravel 11, fully containerized with Docker, and designed around an async queue architecture.

> Upload a video → get back a clean, noise-free version. No third-party AI API needed — all processing runs locally inside the container.

---

## ✨ Features

- **Async processing** — uploads return immediately with a job ID; processing happens in the background
- **AI-powered** — uses DeepFilterNet3 (PyTorch 2.2.0) for neural network speech enhancement
- **Queue architecture** — database-backed job queue with real-time status polling
- **Fallback handling** — if AI processing fails, original file is returned gracefully
- **Fully containerized** — runs entirely inside Docker via Laravel Sail, no manual dependency setup

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 / PHP 8.5 |
| Container | Docker via Laravel Sail |
| Queue | Laravel Database Queue |
| AI Model | DeepFilterNet3 0.5.6 |
| Deep Learning | PyTorch 2.2.0 |
| Audio Processing | FFmpeg 6.1.1 |
| Status Tracking | Laravel Cache |

---

## 🏗 Architecture

The system follows a **queue-based async processing pattern** — the HTTP layer only handles file intake and status reporting, while all heavy AI processing is offloaded to background workers.

```
Client
  │
  ▼
POST /api/upload
  │
  ▼
UploadController → validates file → stores file → dispatches job → returns 202 + job_id
  │
  ▼
Queue Worker → runs DeepFilterNet3 → writes result to cache
  │
  ▼
GET /api/upload/status/{job_id} ← Client polls until completed
  │
  ▼
Client downloads cleaned video from download_url
```

### Job Status Values

| Status | Meaning |
|---|---|
| `pending` | Job is queued, not yet picked up |
| `processing` | Worker is actively running AI processing |
| `completed` | Done — `download_url` is available |
| `fallback` | AI failed, original file returned as output |
| `failed` | Both processing and fallback failed |

---

## 🚀 Getting Started

### Prerequisites
- Docker & Docker Compose

### Run the project

```bash
# 1. Clone the repo
git clone https://github.com/gitnyaDanil/audio-noise-reduction-api.git
cd audio-noise-reduction-api

# 2. Copy environment file
cp .env.example .env

# 3. Start containers
./vendor/bin/sail up -d

# 4. Clear config and cache
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# 5. Start the queue worker (keep this running)
./vendor/bin/sail artisan queue:work --tries=3 --sleep=3 --timeout=180
```

---

## 📡 API Reference

### Upload a Video File

```
POST /api/upload
Content-Type: multipart/form-data
```

**Accepted formats:** MP4, MOV, WEBM, AVI — max 100MB

```bash
curl -X POST http://localhost:8080/api/upload -F "file=@video.mp4"
```

**Response (202 Accepted):**
```json
{
  "message": "File uploaded and queued",
  "job_id": "06b7ecf6-5d93-4348-81ce-1335cc539bb7",
  "status_url": "http://localhost:8080/api/upload/status/06b7ecf6-5d93-4348-81ce-1335cc539bb7"
}
```

---

### Poll Job Status

```
GET /api/upload/status/{job_id}
```

```bash
curl http://localhost:8080/api/upload/status/06b7ecf6-5d93-4348-81ce-1335cc539bb7
```

**Response (completed):**
```json
{
  "status": "completed",
  "progress": 100,
  "message": "AI noise reduction complete",
  "original_path": "uploads/abc123.mp4",
  "processed_path": "processed/06b7ecf6_abc123.mp4",
  "download_url": "http://localhost/storage/processed/06b7ecf6_abc123.mp4"
}
```

---

## ⚠️ Known Limitations

- DeepFilterNet3 is optimized for **speech enhancement** — best results on voice + background noise. Music or non-speech audio may sound unnatural after processing.
- Job status is cached with a **1-hour TTL** — the status endpoint returns 404 after expiry.
- **No file cleanup** — processed files are stored indefinitely on the public disk.
- **Single queue worker** by default — concurrent uploads are processed sequentially.

---

## 🔮 Future Improvements

- [ ] Frontend upload UI with real-time progress bar
- [ ] Redis queue driver for higher throughput
- [ ] S3 storage for scalability
- [ ] Laravel Horizon for queue monitoring
- [ ] Webhook support (callback URL on job completion)
- [ ] Batch processing (multiple files per request)
- [ ] GPU support via CUDA for faster processing
- [ ] Scheduled file cleanup (auto-delete after 24h)

---

## 📁 Key Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/UploadController.php` | Upload and status endpoints |
| `app/Jobs/ProcessDolbyUploadJob.php` | Background job running DeepFilterNet3 |
| `docker/8.5/Dockerfile` | Container with all AI dependencies baked in |
| `compose.yaml` | Docker Compose configuration |
