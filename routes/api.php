<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContentMarketImportController;
use App\Http\Middleware\VerifyContentImportSignature;

/*
|--------------------------------------------------------------------------
| Content Import API
|--------------------------------------------------------------------------
|
| Protected API endpoints for importing markets
| from content.wrangle.win.
|
*/

Route::prefix('content')
    ->middleware([
        VerifyContentImportSignature::class,
        'throttle:30,1',
    ])
    ->group(function () {
        Route::post('/markets', [
            ContentMarketImportController::class,
            'store',
        ])->name('api.content.markets.store');
    });
