<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
Route::get('/upload/status/{id}', [UploadController::class, 'status'])
    ->whereUuid('id')
    ->name('upload.status');
