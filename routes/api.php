<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return ['message' => 'Hello World'];
});

// One file per feature, like modules in NestJS.
require __DIR__.'/api/auth.php';
