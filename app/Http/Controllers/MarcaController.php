<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Marca;


class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            // Forma básica
            // $marcas = Marca::all();

            // Ordenadas
            $marcas = Marca::orderBy('id','desc')->get();

            // Paginadas
            // $marcas = Marca::orderBy('id','desc')->paginate(10);

            return response()->json($marcas);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al obtener las marcas',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            //validaciones a nivel de Request
            $request->validate(
                [
                    'nombre' => 'required|string|min:2|max:80|unique:marcas,nombre'
                ],
                [
                    'nombre.unique' => 'Ya existe una marca con este nombre en la base de datos'
                ]
            );

            $marca = Marca::create([
                'nombre' => $request->nombre
            ]);
            return response()->json([
                'message' => 'Marca registrada correctamente',
                'marca' => $marca
            ],201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errores' => $e->errors()
            ], 422);

        } catch(\Exception $e){
              return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $marca = Marca::findOrFail($id);
            return response()->json($marca);
        }catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Marca no encontrada, con ID = ' . $id
            ], 404);
        }
        catch(\Exception $e){
            return response()->json([
                'message' => 'Marca no encontrada',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
    {
        try{
             //primero obtenemos el registro de la bd
            $marca = Marca::findOrFail($id);
            //aplicamos validaciones a nivel de request
            $request->validate(
                [
                    'nombre' => [
                        'required',
                        'string',
                        'min:2',
                        'max:80',
                        Rule::unique('marcas', 'nombre')->ignore($id)
                    ]
                ],
                [
                    'nombre.unique' => 'Ya existe una marca con este nombre en la base de datos'
                ]
            );

            //mandamos a actualizar el registro
            $marca->update([
                'nombre' =>$request->nombre
            ]);
            return response()->json([
                'message' => 'Marca actualizada correctamente',
                'marca' => $marca
            ],202);
        }catch(\Exception $e){
             return response()->json([
                'message' => 'Marca no encontrada',
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
            $marca = Marca::with('productos')->findOrFail($id);

            if ($marca->productos()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar la marca porque tiene productos asociados.'
                ], 409);
            }

            $marca->delete();

            return response()->json([
                'message' => 'Marca eliminada correctamente.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Marca no encontrada, con el ID = ' .$id
            ], 404);
        }
    }
}
