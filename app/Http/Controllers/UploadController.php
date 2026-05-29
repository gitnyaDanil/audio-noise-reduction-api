<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDolbyUploadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:mp4,mov,webm,avi',
                'max:102400',
            ],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads');

        $jobId = (string) Str::uuid();
        Cache::put("upload_job:{$jobId}", [
            'status' => 'pending',
            'progress' => 0,
            'message' => 'Queued for processing',
            'original_path' => $path,
        ], 3600);

        ProcessDolbyUploadJob::dispatch($path, $jobId);

        return response()->json([
            'message' => 'File uploaded and queued',
            'job_id' => $jobId,
            'status_url' => route('upload.status', ['id' => $jobId]),
        ], 202);
    }

    public function status(string $id)
    {
        $job = Cache::get("upload_job:{$id}");

        if (! $job) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Job ID not found',
            ], 404);
        }

        return response()->json($job, 200);
    }
}

