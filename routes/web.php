<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CSVController;

Route::get('/', [CSVController::class, 'index']);
Route::post('/upload', [CSVController::class, 'upload'])->name('upload');