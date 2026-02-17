<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Imagen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    public function index()
    {
        try {
            $productos = Producto::with(['marca','categoria','imagenes'])
                ->orderBy('id','desc')
                ->get();

            return response()->json($productos, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de productos.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{

            if (!$request->has('producto')) {
                return response()->json([
                    'message' => 'El objeto producto es requerido'
                ], 422);
            }
            // Decodificamos el JSON
            $productoData = json_decode($request->producto, true);

            if (!$productoData) {
                return response()->json([
                    'message' => 'El formato del JSON es inválido'
                ], 422);
            }
            // Normalizar estructura
            $data = [
                'nombre' => $productoData['nombre'] ?? null,
                'descripcion' => $productoData['descripcion'] ?? null,
                'precio' => $productoData['precio'] ?? null,
                'stock' => $productoData['stock'] ?? null,
                'modelo' => $productoData['modelo'] ?? null,
                'marca_id' => $productoData['marca']['id'] ?? null,
                'categoria_id' => $productoData['categoria']['id'] ?? null,
                'activo' => $productoData['activo'] ?? null,
            ];

            // Validaciones datos recibidos
            $validator = Validator::make($data, [
                'nombre' => 'required|string|max:80|unique:productos,nombre',
                'descripcion' => 'required|string|max:200',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'modelo' => 'required|string|max:50',
                'marca_id' => 'required|exists:marcas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'activo' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }


            //iniciamos una transacción porque se debe guardar en ambas tablas
            DB::beginTransaction();
            
            //Guardar el producto
            $producto = Producto::create($data);
            //comprobamos si vienen imagenes en el request para guardarlas
            if($request->hasFile('imagenes')){
                //recorremos la coleccion de imagenes para gestionarlas
                foreach($request->file('imagenes') as $file){
                    //cambiamos el nombre de la imagen
                    $nombreImagen = time().'_'.$file->getClientOriginalName();
                    $rutaDestino = public_path('images/productos');
                    //si no existe la carpeta la creamos
                    if(!file_exists($rutaDestino)){
                        mkdir($rutaDestino, 0755, true);
                    }
                    //copiamos el archivo a la ruta destino
                    $file->move($rutaDestino, $nombreImagen);
                    //guardamos el nombre de la imagen en la tabla
                    //imagenes a través del modelo Imagen
                    Imagen::create([
                        'nombre' => $nombreImagen,
                        'producto_id' => $producto->id
                    ]);
                }
            }
            //confirmamos la transacción
            DB::commit();
            //obtenemos el objeto guardado completo
            $producto->load(['marca','categoria','imagenes']);
            return response()->json([
                'message' => 'Producto registrado correctamente',
                'producto' => $producto
            ],201);
        }catch(ValidationException $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ],422);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor'
            ],500);
        }
    }

    //
}

