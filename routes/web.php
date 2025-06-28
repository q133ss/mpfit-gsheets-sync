<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $service = new App\Services\MpfitService();
    $ar = $service->getArrivals();
    dd($ar);
    return view('welcome');
});
