<?php

use App\Http\Controllers\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('sync')->group(function () {
    Route::post('/products', [SyncController::class, 'apiSyncProducts']);
    Route::post('/stocks', [SyncController::class, 'apiSyncStocks']);
    Route::post('/arrivals', [SyncController::class, 'apiSyncArrivals']);
});
