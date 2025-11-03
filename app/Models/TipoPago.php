<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    protected $table = 'tipos_pagos';
    protected $primaryKey = 'idtipo_pago';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    // 🔁 Relación inversa: un tipo de pago puede tener muchas facturas
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'idtipo_pago', 'idtipo_pago');
    }
}
