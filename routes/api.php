<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostApiController;
use App\Http\Controllers\CategoryApiController;


Route::prefix('api')->group(function () {
    Route::get('posts', [PostApiController::class, 'index']);
    Route::get('posts/{slug}', [PostApiController::class, 'show']);
    Route::get('categories', [CategoryApiController::class, 'index']);
});
