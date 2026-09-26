<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\InquiryController;
use Illuminate\Support\Facades\Route;

Route::controller(AgentController::class)->group(function () {
    Route::get('agents', 'index');
    Route::get('agents/filters', 'filters');
    Route::get('agents/{agent}', 'show')->whereNumber('agent');
});

Route::controller(InquiryController::class)->group(function () {
    Route::post('inquiries', 'store');
    Route::get('my/inquiries', 'mine');
    Route::post('my/inquiries/{inquiry}/read', 'markRead')->whereNumber('inquiry');
});
