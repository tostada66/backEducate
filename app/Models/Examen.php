<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Examen extends Model {
    protected $table='examenes';
    protected $primaryKey='idexamen';
    protected $fillable=['idcurso','titulo','descripcion','intentos'];
    public function curso(){ return $this->belongsTo(Curso::class,'idcurso','idcurso'); }
    public function preguntas(){ return $this->hasMany(Pregunta::class,'idexamen','idexamen'); }
  }