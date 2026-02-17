<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\ProductoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//definimos las rutas para los controladores de productos, categorias y marcas
Route::apiResource('categorias', categoriaController::class);
route::apiResource('productos', ProductoController::class);
//route::apiResource('marcas', MarcaController::class);
