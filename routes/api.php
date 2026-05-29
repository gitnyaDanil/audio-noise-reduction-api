<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::post('/upload', [UploadController::class, 'store']);
Route::get('/upload/status/{id}', [UploadController::class, 'status'])->name('upload.status');