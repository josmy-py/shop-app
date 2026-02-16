<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'correlativo',
        'fecha',
        'fecha_despacho',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'user_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_despacho' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
    
}
