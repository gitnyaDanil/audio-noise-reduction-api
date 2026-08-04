<?php

namespace App\Jobs;

use App\Services\AudioNoiseReducer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Timeout(600)]
#[FailOnTimeout]
class ProcessNoiseReductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $srcPath,
        public string $jobId,
    ) {}

    public function handle(AudioNoiseReducer $reducer): void
    {
        $this->updateStatus('processing', 20, 'Extracting the audio track');

        try {
            $filename = $this->jobId.'.mp4';
            $finalPath = Storage::disk('public')->path('processed/'.$filename);

            $this->updateStatus('processing', 50, 'Applying DeepFilterNet3 speech enhancement');
            $reducer->reduceVideo(Storage::path($this->srcPath), $finalPath);

            $this->updateStatus('completed', 100, 'Noise reduction complete', [
                'download_url' => Storage::disk('public')->url('processed/'.$filename),
            ]);
        } catch (Throwable $exception) {
            Log::error('DeepFilterNet processing failed', [
                'job_id' => $this->jobId,
                'src_path' => $this->srcPath,
                'exception' => $exception->getMessage(),
            ]);

            $this->fallbackToOriginal();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->updateStatus('failed', 0, 'Processing failed. Check the worker logs for details.');

        Log::error('Noise-reduction queue job failed', [
            'job_id' => $this->jobId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    protected function fallbackToOriginal(): void
    {
        try {
            $extension = pathinfo($this->srcPath, PATHINFO_EXTENSION);
            $destination = 'processed/'.$this->jobId.'_original.'.$extension;

            Storage::disk('public')->put($destination, Storage::get($this->srcPath));

            $this->updateStatus('fallback', 100, 'Enhancement failed; the original file is available.', [
                'download_url' => Storage::disk('public')->url($destination),
            ]);
        } catch (Throwable $exception) {
            Log::error('Noise-reduction fallback failed', [
                'job_id' => $this->jobId,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $extra */
    protected function updateStatus(string $status, int $progress, string $message, array $extra = []): void
    {
        $current = Cache::get($this->cacheKey(), []);

        Cache::put($this->cacheKey(), array_merge($current, [
            'job_id' => $this->jobId,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], $extra), config('noise_reduction.status_ttl'));
    }

    protected function cacheKey(): string
    {
        return "upload_job:{$this->jobId}";
    }
}
