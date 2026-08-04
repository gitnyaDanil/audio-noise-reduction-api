# ADR 0001: Process DeepFilterNet jobs asynchronously

- Status: Accepted
- Date: 2026-08-01

## Context

Speech enhancement requires FFmpeg extraction, model inference, and video transcoding. These operations can take longer than a normal HTTP request, especially on CPU. Running them inside the upload request would increase timeout risk and tie application capacity to inference duration.

DeepFilterNet accepts an audio waveform rather than a video container. The application therefore needs an explicit media pipeline around the model.

## Decision

The API stores each validated upload and dispatches a Laravel queue job. The job performs three stages:

1. FFmpeg extracts mono 48 kHz PCM WAV audio.
2. DeepFilterNet3 produces an enhanced WAV file.
3. FFmpeg combines the original video stream and enhanced audio into H.264/AAC MP4.

The API returns HTTP `202` with a job ID. Clients poll a status endpoint. Job status is stored in cache with a TTL. If enhancement fails, the worker copies the original upload to public storage and reports a `fallback` state.

## Consequences

### Positive

- Upload requests return quickly.
- API and inference capacity can scale independently.
- Failures have explicit states and logs.
- The model integration can be tested behind a service boundary.
- Clients can recover the original upload after an inference failure.

### Negative

- The deployment requires a queue worker and shared storage.
- Clients must poll for completion.
- Cache-based status is temporary and not a full audit record.
- Local storage limits horizontal scaling until object storage is added.

## Alternatives considered

### Process during the upload request

Rejected because CPU inference and transcoding can exceed HTTP timeouts and reduce API throughput.

### Call a separate model-serving service immediately

Deferred because the current project is small and uses one model. A separate inference service becomes useful when workers need GPU scheduling or independent scaling.

### Return a failed state without fallback

Rejected because the original upload remains useful and can be published safely with a clear `fallback` status.

## Follow-up decisions

- Replace cache status with a persistent job resource before adding user accounts.
- Move media to object storage before running multiple worker hosts.
- Define retry policy from measured failure modes rather than retrying every processing error.
