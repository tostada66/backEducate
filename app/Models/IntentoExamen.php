<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class IntentoExamen extends Model {
    protected $table='intento_examens';
    protected $primaryKey='idintento';
    protected $fillable=['idexamen','idestudiante','puntaje','porcentaje','iniciado_at','finalizado_at'];
    protected $casts=['iniciado_at'=>'datetime','finalizado_at'=>'datetime'];
    public function examen(){ return $this->belongsTo(Examen::class,'idexamen','idexamen'); }
    public function estudiante(){ return $this->belongsTo(Estudiante::class,'idestudiante','idestudiante'); }
  }