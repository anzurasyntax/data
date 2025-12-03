<?php

use App\Http\Controllers\UploadDataFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [UploadDataFileController::class, 'index'])->name('index');
Route::post('/create', [UploadDataFileController::class, 'create'])->name('create');
