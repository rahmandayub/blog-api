<?php

use App\Http\Controllers\CategoryApiController;
use App\Http\Controllers\PostApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api-key:api-key')->group(function () {
    Route::get('posts', [PostApiController::class, 'index']);
    Route::get('posts/{slug}', [PostApiController::class, 'show']);
    Route::get('categories', [CategoryApiController::class, 'index']);
});
