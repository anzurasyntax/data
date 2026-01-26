<?php

use App\Http\Controllers\FileProcessingController;
use App\Http\Controllers\UploadedFileController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/files')->name('home');

Route::resource('files', UploadedFileController::class)->only(['index', 'store', 'destroy']);
Route::get('/files/{id}/quality', [UploadedFileController::class, 'quality'])->name('files.quality');

Route::resource('process', FileProcessingController::class)->only(['index', 'show']);

Route::put('/files/{id}/cell', [FileProcessingController::class, 'updateCell']);
Route::post('/files/{id}/clean', [FileProcessingController::class, 'cleanData'])->name('files.clean');
Route::get('/files/{id}/quality-check', [FileProcessingController::class, 'qualityCheck'])->name('files.quality-check');
