<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('auth')->group(function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function(){
        Route::get('me',[AuthController::class, 'me']);
        Route::post('logout',[AuthController::class, 'logout']);
        Route::post('refresh',[AuthController::class, 'refresh']);
    });
});

//definimos las rutas para los controladores de productos, categorias y marcas
Route::apiResource('categorias', categoriaController::class);
route::apiResource('productos', ProductoController::class);
route::apiResource('marcas', MarcaController::class);
route::apiResource('orders', OrderController::class);
//definimos una ruta para obtener las órdenes de un usuario específico
Route::put('ordenes/estado/{id}', [OrderController::class, 'gestionarEstado']);
Route::patch('productos/{id}/toggle-activo',[ProductoController::class,'toggleActivo']);