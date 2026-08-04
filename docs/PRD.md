# Product Requirements Document

## Product summary

The AI Audio Noise Reduction API accepts a short video, improves the clarity of its speech track with DeepFilterNet3, and returns a processed MP4 through an asynchronous API.

## Problem

Content creators and media applications often receive videos recorded near traffic, fans, keyboards, or other background noise. Cleaning these files manually takes time, while running speech enhancement inside an HTTP request can cause timeouts and poor reliability.

## Target users

- Developers adding speech enhancement to a media workflow
- Content creators processing short spoken videos
- Internal media teams that need repeatable batch processing

## Goals

- Accept common short-video formats through a simple HTTP API.
- Keep long-running inference outside the request lifecycle.
- Produce a broadly compatible H.264/AAC MP4.
- Expose clear progress and terminal states.
- Preserve access to the original file when enhancement fails.
- Run locally through a reproducible Docker Compose environment.

## Non-goals

- Training or fine-tuning a speech-enhancement model
- Real-time streaming enhancement
- Music restoration or source separation
- Speaker diarization or transcription
- Permanent media hosting

## Primary user flow

1. The client uploads a supported video.
2. The API validates and stores the upload.
3. The API creates a job status and returns HTTP `202` with a job ID.
4. A queue worker extracts a mono 48 kHz WAV track.
5. DeepFilterNet3 enhances the speech track.
6. FFmpeg combines the original video with the enhanced audio.
7. The client polls the status endpoint and receives a download URL.
8. If enhancement fails, the worker publishes the original upload as a fallback.

## Functional requirements

| ID | Requirement | Acceptance criterion |
|---|---|---|
| FR-1 | Upload video | The API accepts MP4, MOV, WebM, and AVI files within the configured size limit. |
| FR-2 | Reject invalid input | Missing, unsupported, or oversized uploads return HTTP `422`. |
| FR-3 | Asynchronous processing | A valid upload returns HTTP `202` and a UUID without waiting for inference. |
| FR-4 | Status reporting | A client can retrieve `pending`, `processing`, `completed`, `fallback`, or `failed`. |
| FR-5 | Speech enhancement | The worker extracts 48 kHz mono PCM audio and passes it to DeepFilterNet3. |
| FR-6 | Video output | Successful processing produces a non-empty H.264/AAC MP4. |
| FR-7 | Graceful fallback | An enhancement error makes the original video available when storage succeeds. |
| FR-8 | Safe errors | API responses do not expose command output, stack traces, or internal paths. |

## Non-functional requirements

| Area | Requirement |
|---|---|
| Reliability | Queue retry timing must exceed the job timeout to reduce duplicate processing. |
| Performance | Record end-to-end duration and real-time factor during evaluation. |
| Security | Validate type and size, generate server-side filenames, and avoid returning internal errors. |
| Maintainability | Keep media processing behind a dedicated service and configuration file. |
| Portability | Pin the Python, PyTorch, and DeepFilterNet runtime in Docker. |
| Observability | Log failures with the job ID and expose stable client-facing states. |
| Retention | Status records expire after the configured TTL; automatic file deletion is planned. |

## Success metrics

- At least 95% successful completion across the portfolio evaluation set
- Zero HTTP request timeouts during normal asynchronous processing
- Processing duration and real-time factor reported for every evaluation sample
- Measurable STOI, PESQ, or SI-SDR improvement when clean reference audio exists
- No internal exception details exposed through API responses

The numeric quality and latency targets will be set after the first reproducible benchmark. This avoids claiming model performance before testing the complete pipeline.

## Constraints and assumptions

- The current runtime uses CPU inference.
- DeepFilterNet3 targets speech enhancement, not general audio restoration.
- Uploaded and processed files use local Laravel storage.
- Job state uses Laravel cache and the queue uses the database connection.
- Clients poll for status; callbacks and WebSockets are outside the first release.

## Release criteria

- Automated API and queue orchestration tests pass.
- A real video completes through the Docker pipeline.
- Before-and-after samples and benchmark results are published.
- The public README documents setup, API responses, limitations, and architecture.
- Expired-file handling is either implemented or clearly documented as a limitation.

## Future scope

- Persistent job records with ownership and audit history
- Automatic media cleanup
- Signed object-storage downloads
- Authentication and rate limiting
- Queue and inference metrics
- Optional GPU worker profile
- Web demo with side-by-side audio comparison
