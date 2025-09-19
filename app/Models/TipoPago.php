<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    protected $table = 'tipos_pagos';
    protected $primaryKey = 'idpago';
    protected $fillable = ['nombre','descripcion'];
}
