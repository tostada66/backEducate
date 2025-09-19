<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $table = 'matriculas';
    protected $primaryKey = 'idmatricula';
    protected $fillable = ['idestudiante','idcurso','fecha','estado'];

    public function estudiante(){ return $this->belongsTo(Estudiante::class,'idestudiante','idestudiante'); }
    public function curso(){ return $this->belongsTo(Curso::class,'idcurso','idcurso'); }
    public function progresos(){ return $this->hasMany(ProgresoClase::class,'idmatricula','idmatricula'); }
}
