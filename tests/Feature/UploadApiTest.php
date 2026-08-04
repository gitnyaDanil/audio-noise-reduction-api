<?php

namespace Tests\Feature;

use App\Jobs\ProcessNoiseReductionJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadApiTest extends TestCase
{
    public function test_video_upload_is_stored_and_queued(): void
    {
        Storage::fake('local');
        Queue::fake();

        $response = $this->postJson('/api/upload', [
            'file' => UploadedFile::fake()->create('sample.mp4', 1024, 'video/mp4'),
        ]);

        $response
            ->assertAccepted()
            ->assertJsonStructure(['message', 'job_id', 'status_url']);

        $jobId = $response->json('job_id');
        $status = Cache::get("upload_job:{$jobId}");

        $this->assertSame('pending', $status['status']);
        $this->assertSame('sample.mp4', $status['original_filename']);

        Queue::assertPushed(ProcessNoiseReductionJob::class, function ($job) use ($jobId) {
            Storage::disk('local')->assertExists($job->srcPath);

            return $job->jobId === $jobId;
        });
    }

    public function test_upload_rejects_unsupported_files(): void
    {
        Queue::fake();

        $this->postJson('/api/upload', [
            'file' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_status_endpoint_returns_not_found_for_unknown_job(): void
    {
        $this->getJson('/api/upload/status/5a27a59b-74bb-4216-9c69-102dbc4f6381')
            ->assertNotFound()
            ->assertJson([
                'status' => 'not_found',
                'message' => 'Job ID not found',
            ]);
    }
}
