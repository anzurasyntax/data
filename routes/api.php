<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UploadedFileController;

// API Routes
// Note: To add authentication, wrap routes with: Route::middleware('auth:sanctum')->group(function () { ... });
// For public APIs, authentication is optional but CSRF protection is handled automatically via Laravel's API middleware

Route::post('/files/upload', [UploadedFileController::class, 'upload']);

Route::get('/files', [UploadedFileController::class, 'index']);

Route::get('/file/{id}', [UploadedFileController::class, 'show']);

Route::delete('/file/{id}', [UploadedFileController::class, 'destroy']);

Route::get('/file/{id}/quality-check', [UploadedFileController::class, 'qualityCheck']);
Route::post('/file/{id}/clean', [UploadedFileController::class, 'cleanData']);
