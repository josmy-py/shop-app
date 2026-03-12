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

    public function show(string $id)
    {
        try{
            $producto = Producto::with(['marca','categoria','imagenes'])->findOrFail($id);
            return response()->json($producto);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'message' => 'No se ha encontrado el producto con ID = ' . $id
            ],404);
        }
    }

    public function update(Request $request, string $id)
    {
        try{
            //obtenemos el producto de bd
            $producto = Producto::with('imagenes')->findOrFail($id);
            //veriflicanmos se sea un objeto el que se recibe
            if(!$request->has('producto')){
                return response()->json([
                    'message' => 'El objeto producto es requerido'
                ],422);
            }
            //decodificamos el objeto producto
            $productoData = json_decode($request->producto,true);
            //verificamos que tenga un formato válido
            if(json_last_error() !== JSON_ERROR_NONE){
                return response()->json([
                    'message' => 'El JSON enviado en producto no es válido',
                    'error' => json_last_error_msg()
                ],422);
            }
            //validamos estructura 
            $validator = Validator::make($productoData, [
                'nombre' => 'required|string|max:80|unique:productos,nombre,' .$id,
                'descripcion' => 'required|string|max:200',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'modelo' => 'required|string|max:50',
                'marca.id' => 'required|exists:marcas,id',
                'categoria.id' => 'required|exists:categorias,id',
                'activo' => 'required|boolean',
            ]);
            if($validator->fails()){
                return response()->json([
                    'message' => 'Existen errores de validación',
                    'error' => $validator->errors()
                ],500);
            }
            //iniciamos la transación
            DB::beginTransaction();
            //transformamos datos al formato de base de datos
             $data = [
                'nombre' => $productoData['nombre'],
                'descripcion' => $productoData['descripcion'],
                'precio' => $productoData['precio'],
                'stock' => $productoData['stock'],
                'modelo' => $productoData['modelo'],
                'marca_id' => $productoData['marca']['id'],
                'categoria_id' => $productoData['categoria']['id'],
                'activo' => $productoData['activo'],
            ];
            //guardamos en producto
            $producto->update($data);
            //gestionamos las imagenes
           if($request->hasFile('imagenes')){
                //eliminamos fisicamente las imagenes anteriores
                foreach($producto->imagenes as $img){
                    $ruta = public_path('images/productos/'. $img->nombre);
                    if(file_exists($ruta)){
                        unlink($ruta); //borramos el archivo físico
                    }
                    //borramos el registro de imagenes
                    $img->delete();
                } 
                //guardamos las nuevas imágenes
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
           return response()->json([
                'message' => 'Producto actualizado correctamente',
                'producto' => $producto->load(['marca','categoria','imagenes'])
           ],202);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'message' => 'No se encuentra el producto con ID = ' . $id
            ],404);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage()
            ],500);
        }
    }
    
    public function destroy(string $id)
    {
        try {
        // Buscar el producto con sus imágenes
        $producto = Producto::with('imagenes')->findOrFail($id);
        DB::beginTransaction();

        // Eliminar imágenes físicas
        foreach ($producto->imagenes as $img) {
            $ruta = public_path('images/productos/' . $img->nombre);

            if (file_exists($ruta)) {
                unlink($ruta);
            }

            // Eliminar registro de la imagen
            $img->delete();
        }

        // Eliminar el producto
        $producto->delete();

        DB::commit();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'message' => 'No se encontró el producto con ID = ' . $id
        ], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error al eliminar el producto',
            'error' => $e->getMessage()
        ], 500);
    }
    }
        public function toggleActivo($id)
    {

        $producto = Producto::findOrFail($id);

        $producto->activo = !$producto->activo;

        $producto->save();

        return response()->json([
            'message' => $producto->activo
                ? 'Producto activado'
                : 'Producto desactivado'
        ]);

    }

}
