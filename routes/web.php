<?php

use App\Http\Controllers\UploadDataFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [UploadDataFileController::class, 'index'])->name('index');

Route::post('/create', [UploadDataFileController::class, 'create'])->name('create');


Route::get('/files', [UploadDataFileController::class, 'getAllFiles'])->name('files');

Route::get('/process-file/{id}', [UploadDataFileController::class, 'processFile'])->name('process.file');
