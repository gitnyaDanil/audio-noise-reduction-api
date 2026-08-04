<?php

namespace Tests\Unit;

use App\Jobs\ProcessNoiseReductionJob;
use App\Services\AudioNoiseReducer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessNoiseReductionJobTest extends TestCase
{
    public function test_job_publishes_processed_video_when_enhancement_succeeds(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::put('uploads/input.mp4', 'original-video');

        $reducer = Mockery::mock(AudioNoiseReducer::class);
        $reducer->shouldReceive('reduceVideo')
            ->once()
            ->andReturnUsing(function (string $input, string $output): void {
                $this->assertFileExists($input);
                File::ensureDirectoryExists(dirname($output));
                file_put_contents($output, 'processed-video');
            });

        $job = new ProcessNoiseReductionJob('uploads/input.mp4', 'job-123');
        $job->handle($reducer);

        $status = Cache::get('upload_job:job-123');

        $this->assertSame('completed', $status['status']);
        $this->assertSame(100, $status['progress']);
        $this->assertStringContainsString('job-123.mp4', $status['download_url']);
    }

    public function test_job_exposes_original_file_when_enhancement_fails(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::put('uploads/input.mp4', 'original-video');

        $reducer = Mockery::mock(AudioNoiseReducer::class);
        $reducer->shouldReceive('reduceVideo')
            ->once()
            ->andThrow(new RuntimeException('model error'));

        $job = new ProcessNoiseReductionJob('uploads/input.mp4', 'job-456');
        $job->handle($reducer);

        $status = Cache::get('upload_job:job-456');

        $this->assertSame('fallback', $status['status']);
        Storage::disk('public')->assertExists('processed/job-456_original.mp4');
    }
}
