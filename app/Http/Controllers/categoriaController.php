<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class categoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            //resultado ordenado por id de forma descendente
            //$categorias = Categoria::all();
            $categoria = Categoria::orderBy('id', 'desc')->get();


            return response()->json($categorias,);
        }catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error al obtener las categorías', 'error' => $e->getMessage()], 500);
        }
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            //validamos a nivel request
            $validated = $request->validate([
                'nombre' => 'required|string|min:2|max:80|unique:categorias,nombre'
            ,],
            [
                'nombre.required' => 'El nombre de la categoría es obligatorio',
                'nombre.string' => 'El nombre de la categoría debe ser una cadena de texto',
                'nombre.min' => 'El nombre de la categoría debe tener al menos 2 caracteres',
                'nombre.max' => 'El nombre de la categoría no debe exceder los 80 caracteres',
                'nombre.unique' => 'El nombre de la categoría ya existe'
            ]
            );
            //insertamos la categoria en la base de datos
            $categoria = Categoria::create($validated);
            return response()->json([
                'mensaje' => 'Categoria registrada exitosamente',
                'categoria' => $categoria
            ],201);

        }catch (validationExcepcion $e) {
            return response()->json([
                'mensaje' => 'Error al obtener las categorías',
                'error' => $e->getMessage()
                ],500);

        }catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'ocurrio un error inesperado al registrar la categoria',
                'error' => $e->getMessage()
                ],500);
        }
        //
    }

    /**
     * Display the specified resource.
     */
        public function show(string $id)
        {
            try {
                $categoria = Categoria::findOrFail($id);
                return response()->json($categoria);
            }catch (ModelNotFoundException $e) {
                return response()->json([
                    'mensaje' => 'categoría  no encontrada  con ID = ' .$id,
                    ],404);
            }
            catch (\Exception $e) {
                return response()->json([
                    'mensaje' => 'Ocurrió un error inesperado al obtener la categoría con ID = ' .$id,
                    'error' => $e->getMessage()
                    ],500);
            }
            //
        }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
             //primero obtenemos el registro de la bd
            $categoria = Categoria::findOrFail($id);
            //aplicamos validaciones a nivel de request
            $request->validate(
                [
                    'nombre' => [
                        'required',
                        'string',
                        'min:2',
                        'max:80',
                        Rule::unique('categorias', 'nombre')->ignore($id)
                    ]
                ],
                [
                    'nombre.unique' => 'Ya existe una marca con este nombre en la base de datos'
                ]
            );

            //mandamos a actualizar el registro
            $categoria->update([
                'nombre' =>$request->nombre
            ]);
            return response()->json([
                'message' => 'Categoria actualizada correctamente',
                'categoria' => $categoria
            ],202);    
        }catch(ModelNotFoundException $e){
            return response()->json([
                'message' => 'categoría no encontrada con ID = ' .$id,
                'error' => $e->getMessage()
            ],500);
        }
        catch(\Exception $e){
            return response()->json([
                'message' => 'error inesprado del servidor al actualizar la categoria',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    { 
        try {
            $categoria = categoria::with('productos')->findOrFail($id);

            if ($categoria->productos()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar la marca porque tiene productos asociados.'
                ], 409);
            }

            $categoria->delete();

            return response()->json([
                'message' => 'Categoria eliminada correctamente.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Categoria no encontrada, con el ID = ' .$id
            ], 404);
        }
        //
    }
}
