<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'metodo',
        'referencia',
        'monto',
        'estado',
        'respuesta_pasarela',
        'order_id'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'respuesta_pasarela' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}