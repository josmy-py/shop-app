<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = "order_items";

    protected $fillable = [
        'cantidad',
        'precio_unitario',
        'subtotal',
        'producto_id',
        'order_id'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

