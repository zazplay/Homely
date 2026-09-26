<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Middleware (auth, throttle) is declared on the controller methods via #[Middleware].
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::get('me', 'me');
    Route::post('logout', 'logout');
});
