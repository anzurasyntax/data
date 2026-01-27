<?php

use App\Http\Controllers\FileProcessingController;
use App\Http\Controllers\UploadedFileController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('files.upload')
        : redirect()->route('auth.login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');

Route::middleware('auth')->group(function () {
    // Upload page (also shows user's uploaded files)
    Route::get('/files', [UploadedFileController::class, 'index'])->name('files.upload');
    Route::post('/files', [UploadedFileController::class, 'store'])->name('files.store');
    Route::delete('/files/{slug}', [UploadedFileController::class, 'destroy'])->name('files.delete');
    Route::get('/files/{slug}/quality', [UploadedFileController::class, 'quality'])->name('files.quality');

    // Listing + preview/processing
    Route::get('/my-files', [FileProcessingController::class, 'index'])->name('files.list');
    Route::get('/my-files/{slug}', [FileProcessingController::class, 'show'])->name('files.preview');

    // File operations (slug-based)
    Route::put('/files/{slug}/cell', [FileProcessingController::class, 'updateCell'])->name('files.cell.update');
    Route::post('/files/{slug}/clean', [FileProcessingController::class, 'cleanData'])->name('files.clean');
    Route::get('/files/{slug}/quality-check', [FileProcessingController::class, 'qualityCheck'])->name('files.quality-check');
});
