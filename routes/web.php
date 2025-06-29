<?php

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SyncController::class, 'showSyncPage'])->name('sync.page');
Route::post('/sync/products', [SyncController::class, 'syncProducts'])->name('sync.products');
Route::post('/sync/stocks', [SyncController::class, 'syncStocks'])->name('sync.stocks');
Route::post('/sync/arrivals', [SyncController::class, 'syncArrivals'])->name('sync.arrivals');
