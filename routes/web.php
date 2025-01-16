<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/chat', [ChatController::class, 'chat'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::view('/chatview', 'chat');