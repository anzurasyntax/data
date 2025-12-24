<?php

use App\Http\Controllers\FileProcessingController;
use App\Http\Controllers\UploadedFileController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/files')->name('home');

Route::resource('files', UploadedFileController::class)->only(['index', 'store', 'destroy']);

Route::resource('process', FileProcessingController::class)->only(['index', 'show']);

Route::put('/files/{id}/cell', [FileProcessingController::class, 'updateCell']);
