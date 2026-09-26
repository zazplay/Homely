<?php

use App\Http\Controllers\Api\ApartmentController;
use Illuminate\Support\Facades\Route;

// Middleware and authorization are declared on the controller methods via #[Middleware] / #[Authorize].
Route::controller(ApartmentController::class)->group(function () {
    Route::get('apartments', 'index');
    Route::get('catalog/stats', 'stats');
    Route::get('my/apartments', 'mine');
    Route::post('apartments', 'store');
    Route::get('apartments/{apartment}', 'show');
    Route::patch('apartments/{apartment}', 'update');
    Route::delete('apartments/{apartment}', 'destroy');

    Route::post('apartments/{apartment}/photos', 'addPhotos');
    // scopeBindings: {photo} must belong to {apartment}, otherwise 404.
    Route::delete('apartments/{apartment}/photos/{photo}', 'deletePhoto')->scopeBindings();
});
