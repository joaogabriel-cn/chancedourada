<?php

use App\Http\Controllers\Api\Profile\AffiliateController;
use Illuminate\Support\Facades\Route;

Route::prefix('affiliates')
    ->group(function ()
    {
        Route::get('/', [AffiliateController::class, 'index']);
        Route::get('/generate', [AffiliateController::class, 'generateCode']);
        Route::post('/request', [AffiliateController::class, 'makeRequest']);
        Route::get('/statistics', [AffiliateController::class, 'getStatistics']);
        Route::get('/test-auth', [AffiliateController::class, 'testAuth']);
        Route::get('/test-queries', [AffiliateController::class, 'testQueries']);
    });
