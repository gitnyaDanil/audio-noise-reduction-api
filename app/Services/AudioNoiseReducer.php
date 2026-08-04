<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class AudioNoiseReducer
{
    /**
     * Extract a 48 kHz WAV track, enhance it with DeepFilterNet3, and mux it
     * back into a broadly compatible H.264/AAC MP4 file.
     */
    public function reduceVideo(string $inputPath, string $outputPath): void
    {
        if (! is_file($inputPath)) {
            throw new RuntimeException('Input media file does not exist.');
        }

        $workspace = storage_path('app/tmp/noise-reduction/'.Str::uuid());
        $inputAudio = $workspace.'/input.wav';
        $enhancedDirectory = $workspace.'/enhanced';

        File::ensureDirectoryExists($enhancedDirectory);
        File::ensureDirectoryExists(dirname($outputPath));

        try {
            $this->run([
                config('noise_reduction.ffmpeg_binary'),
                '-hide_banner',
                '-loglevel', 'error',
                '-y',
                '-i', $inputPath,
                '-vn',
                '-ac', '1',
                '-ar', '48000',
                '-c:a', 'pcm_s16le',
                $inputAudio,
            ]);

            $this->run([
                config('noise_reduction.deepfilter_binary'),
                $inputAudio,
                '--model-base-dir', config('noise_reduction.model'),
                '--output-dir', $enhancedDirectory,
            ]);

            $enhancedAudio = collect(File::files($enhancedDirectory))
                ->first(fn ($file) => strtolower($file->getExtension()) === 'wav');

            if ($enhancedAudio === null) {
                throw new RuntimeException('DeepFilterNet did not produce an enhanced WAV file.');
            }

            $this->run([
                config('noise_reduction.ffmpeg_binary'),
                '-hide_banner',
                '-loglevel', 'error',
                '-y',
                '-i', $inputPath,
                '-i', $enhancedAudio->getPathname(),
                '-map', '0:v:0',
                '-map', '1:a:0',
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-crf', '20',
                '-c:a', 'aac',
                '-b:a', '192k',
                '-shortest',
                '-movflags', '+faststart',
                $outputPath,
            ]);

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('The processed video was not created.');
            }
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    /** @param array<int, string> $command */
    protected function run(array $command): void
    {
        Process::timeout(config('noise_reduction.process_timeout'))
            ->run($command)
            ->throw();
    }
}
