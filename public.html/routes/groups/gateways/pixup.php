<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gateway\PixUpController;

Route::prefix('pixup')->group(function () {
    Route::post('create-charge', [PixUpController::class, 'createCharge']);
    Route::post('callback', [PixUpController::class, 'callback']);
    Route::post('consult-status', [PixUpController::class, 'consultStatus']);
});