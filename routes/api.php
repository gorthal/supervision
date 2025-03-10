<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Routes pour l'agent de surveillance
Route::post('/logs', [LogController::class, 'store']);
