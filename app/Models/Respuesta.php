<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model {
    protected $table='respuestas';
    protected $primaryKey='idrespuesta';
    protected $fillable=['idpregunta','texto_opcion','escorrecta','orden'];
    protected $casts=['escorrecta'=>'boolean'];
    public function pregunta(){ return $this->belongsTo(Pregunta::class,'idpregunta','idpregunta'); }
  }