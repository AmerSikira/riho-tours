<?php

use App\Http\Controllers\Api\V1\ArrangementsApiController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\ClientsApiController;
use App\Http\Controllers\Api\V1\PackagesApiController;
use App\Http\Controllers\Api\V1\ReservationsApiController;
use App\Http\Controllers\Api\V1\WebReservationsApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'issue']);

    Route::middleware('api_token')->group(function (): void {
        Route::get('me', [AuthTokenController::class, 'me']);
        Route::get('auth/token/status', [AuthTokenController::class, 'status']);

        Route::apiResource('arrangements', ArrangementsApiController::class);
        Route::apiResource('packages', PackagesApiController::class);
        Route::apiResource('clients', ClientsApiController::class);
        Route::apiResource('reservations', ReservationsApiController::class);
        Route::get('web-rezervacije', [WebReservationsApiController::class, 'index']);
        Route::post('web-rezervacije', [WebReservationsApiController::class, 'store']);
        Route::get('web-rezervacije/{webReservation}', [WebReservationsApiController::class, 'show']);
    });
});
