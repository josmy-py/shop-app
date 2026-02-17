<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\categoriaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//definimos las rutas para los controladores de productos, categorias y marcas
Route::apiResource('categorias', categoriaController::class);
