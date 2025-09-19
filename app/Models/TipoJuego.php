<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoJuego extends Model {
    protected $table='tipo_juegos';
    protected $primaryKey='idtipojuego';
    protected $fillable=['nombre'];
  }
