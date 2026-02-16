<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'modelo',
        'activo',
        'marca_id',
        'categoria_id'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'decimal:2',
        'activo' => 'boolean'
    ];
        //relacion en inversa 
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function imagenes()
    {
        return $this->hasMany(Imagen::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
