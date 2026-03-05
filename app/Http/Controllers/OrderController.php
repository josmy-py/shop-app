<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\orderItem;
use App\Models\Producto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()

    {
        try{
            // Lógica para obtener la lista de órdenes
            $query = Order::with(['user','items.producto', 'pagos'])->get();
            // filtramos por el usuario autenticado
            if (request()->estado) {
                $query->where('estado', request()->estado);
            }
            // filtramos por el usuario autenticado
            if (request()->user_id) {
                $query->where('user_id', request()->user_id);
            }
            //definimos la variable orders para almacenar el resultado de la consulta
            $orders = $query->orderBy('created_at', 'desc')->get();


            return response()->json($orders);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de órdenes.',
                'error' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // validar los datos de entrada
            $data = $request->validate([
                'user_id' => 'required|exists:users,id',
                'fecha' => 'required|date',
                'subtotal' => 'required|numeric|min:0',
                'impuesto' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.producto_id' => 'required|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1',
            ]);
            DB::beginTransaction(); // Iniciar una transacción

            $order = Order::create([
                'correlativo' => $this->generateCorrelativo(),
                'fecha' => $data['fecha'],
                'subtotal' => $data['subtotal'],
                'impuesto' => $data['impuesto'],
                'total' => $data['total'],
                'estado' => 'pendiente',
                'user_id' => $data['user_id'],
            ]);

            foreach ($data['items'] as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $subtotalItem = $producto->precio * $item['cantidad'];

                OrderItem::create([
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotalItem,
                    'producto_id' => $item['producto_id'],
                    'order_id' => $order->id,
                ]);
            }
            DB::commit(); // Confirmar la transacción

            return response()->json([
                'message' => 'Orden creada exitosamente.',
                'order' => $order->load('items.producto')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de error
            return response()->json([
                'message' => 'Error al crear la orden.',
                'error' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
            try {
                $order = Order::findOrFail($id);
                return response()->json($order);
            }catch (ModelNotFoundException $e) {
                return response()->json([
                    'mensaje' => 'orden  no encontrada  con ID = ' .$id,
                    ],404);
            }
            catch (\Exception $e) {
                return response()->json([
                    'mensaje' => 'Ocurrió un error inesperado al obtener la orden con ID = ' .$id,
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        //
    }
    public function GestionarEstado(Request $request,$id){
        try {
            //odtener la orden de la bd
            $order = Order::findOrFail($id);
            //validar el nuevo estado
            $data = $request->validate([
                'estado' => 'required|string|in:pendiente,pagada,cancelada,rembolsada,entregada',
            ]);
            //actualizar el estado de la orden
            $nuevoEstado = $data['estado'];
            //odtener el estado actual de la orden
            $estadoActual = $order->estado;
            //definimos reglas de transición de estados
            $transicionesValidas = [
                'pendiente' => ['pagada', 'cancelada'],
                'pagada' => ['entregada', 'reembolsada'],
                'entregada' => ['reembolsada'],
                'reembolsada' => [],
                'cancelada' => [],
            ];
            //verificar si la transición es válida
            if (!in_array($nuevoEstado, $transicionesValidas[$estadoActual])) {
                return response()->json([
                    'message' => "no se puede cambiar de  $estadoActual a $nuevoEstado",
                ], 400);
            }
            //actualizar el estado de la orden
            $order->estado = $nuevoEstado;
            //si estado == 'entregada' actualiza la fecha de entrega
            if ($nuevoEstado == 'entregada') {
                $order->fecha_entrega = now();
            }
            $order->update();
            return response()->json([
                'message' => "la orden $order->correlativo ha sido actualizada a estado $nuevoEstado",
                'order' => $order->load('items.producto')
            ], 200);



        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error inesperado al cancelar la orden con ID = ' . $id,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function generateCorrelativo()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $ultimo = Order::whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->lockForUpdate()
            ->count();
            $numero = str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
            return $year . $month . $numero;
    }
}
