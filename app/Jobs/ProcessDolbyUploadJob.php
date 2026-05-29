<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ProcessDolbyUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $srcPath;
    public string $jobId;

    public function __construct(string $srcPath, string $jobId)
    {
        $this->srcPath = $srcPath;
        $this->jobId = $jobId;
    }

    public function handle(): void
    {
        Cache::put("upload_job:{$this->jobId}", [
            'status' => 'processing',
            'progress' => 20,
            'message' => 'Processing started',
            'original_path' => $this->srcPath,
        ], 3600);

        try {
            $srcFullPath = Storage::path($this->srcPath);
            $filename = $this->jobId . '_' . basename($this->srcPath);
            $outputDir = storage_path('app/public/processed');
            $expectedOutput = $outputDir . '/' . pathinfo(basename($this->srcPath), PATHINFO_FILENAME) . '_DeepFilterNet3.mp4';
            $finalPath = $outputDir . '/' . $filename;

            Storage::disk('public')->makeDirectory('processed');

            Cache::put("upload_job:{$this->jobId}", [
                'status' => 'processing',
                'progress' => 50,
                'message' => 'Applying AI noise reduction',
                'original_path' => $this->srcPath,
            ], 3600);

            $cmd = sprintf(
                'deepFilter %s --output-dir %s 2>&1',
                escapeshellarg($srcFullPath),
                escapeshellarg($outputDir)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new \RuntimeException(
                    'DeepFilterNet failed: ' . implode("\n", array_slice($output, -5))
                );
            }

            // Rename to our job-prefixed filename
            rename($expectedOutput, $finalPath);

            $downloadUrl = url('/storage/processed/' . $filename);

            Cache::put("upload_job:{$this->jobId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'AI noise reduction complete',
                'original_path' => $this->srcPath,
                'processed_path' => 'processed/' . $filename,
                'download_url' => $downloadUrl,
            ], 3600);

        } catch (\Throwable $e) {
            \Log::error('DeepFilterNet processing failed', [
                'job_id' => $this->jobId,
                'src_path' => $this->srcPath,
                'exception' => $e->getMessage(),
            ]);
            $this->fallback('DeepFilterNet failed: ' . $e->getMessage());
        }
    }

    protected function fallback(string $reason): void
    {
        try {
            $filename = basename($this->srcPath);
            $dest = 'processed/' . $this->jobId . '_' . $filename;
            $sourceStream = Storage::get($this->srcPath);
            Storage::disk('public')->put($dest, $sourceStream);

            $downloadUrl = url('/storage/' . $dest);

            Cache::put("upload_job:{$this->jobId}", [
                'status' => 'fallback',
                'progress' => 100,
                'message' => 'Fallback processing used: '.$reason,
                'original_path' => $this->srcPath,
                'processed_path' => $dest,
                'download_url' => $downloadUrl,
            ], 3600);
        } catch (\Throwable $e) {
            $cleanReason = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', mb_convert_encoding($reason . ' + fallback failed: ' . $e->getMessage(), 'UTF-8', 'UTF-8'));
            Cache::put("upload_job:{$this->jobId}", [
                'status' => 'failed',
                'progress' => 0,
                'message' => $cleanReason,
            ], 3600);
        }
    }
}

