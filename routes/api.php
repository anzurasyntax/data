<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UploadedFileController;

Route::post('/files/upload', [UploadedFileController::class, 'upload']);

Route::get('/files', [UploadedFileController::class, 'index']);

Route::get('/file/{id}', [UploadedFileController::class, 'show']);
