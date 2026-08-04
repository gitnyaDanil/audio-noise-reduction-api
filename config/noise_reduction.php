<?php

return [
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'deepfilter_binary' => env('DEEPFILTER_BINARY', 'deepFilter'),
    'model' => env('DEEPFILTER_MODEL', 'DeepFilterNet3'),
    'process_timeout' => (int) env('NOISE_REDUCTION_PROCESS_TIMEOUT', 600),
    'status_ttl' => (int) env('NOISE_REDUCTION_STATUS_TTL', 86400),
    'max_upload_kb' => (int) env('NOISE_REDUCTION_MAX_UPLOAD_KB', 102400),
];
