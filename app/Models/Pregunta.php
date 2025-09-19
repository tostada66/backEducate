<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Pregunta extends Model {
    protected $table='preguntas';
    protected $primaryKey='idpregunta';
    protected $fillable=['idexamen','pregunta','tipo','puntuacion'];
    public function examen(){ return $this->belongsTo(Examen::class,'idexamen','idexamen'); }
    public function respuestas(){ return $this->hasMany(Respuesta::class,'idpregunta','idpregunta'); }
  }