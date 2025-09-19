<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoJuego extends Model {
    protected $table='intento_juegos';
    protected $primaryKey='idintento';
    protected $fillable=['idjuego','idestudiante','puntaje','porcentaje','iniciado_at','finalizado_at'];
    protected $casts=['iniciado_at'=>'datetime','finalizado_at'=>'datetime'];
    public function juego(){ return $this->belongsTo(Juego::class,'idjuego','idjuego'); }
    public function estudiante(){ return $this->belongsTo(Estudiante::class,'idestudiante','idestudiante'); }
  }