<?php

use App\Http\Controllers\FileProcessingController;
use App\Http\Controllers\UploadedFileController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('files.index')
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
    Route::resource('files', UploadedFileController::class)->only(['index', 'store', 'destroy']);
    Route::get('/files/{id}/quality', [UploadedFileController::class, 'quality'])->name('files.quality');

    Route::resource('process', FileProcessingController::class)->only(['index', 'show']);

    Route::put('/files/{id}/cell', [FileProcessingController::class, 'updateCell']);
    Route::post('/files/{id}/clean', [FileProcessingController::class, 'cleanData'])->name('files.clean');
    Route::get('/files/{id}/quality-check', [FileProcessingController::class, 'qualityCheck'])->name('files.quality-check');
});
