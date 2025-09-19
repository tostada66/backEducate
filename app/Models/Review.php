<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'idreview';
    protected $fillable = ['idcurso','idestudiante','rating','comentario'];

    public function curso(){ return $this->belongsTo(Curso::class,'idcurso','idcurso'); }
    public function estudiante(){ return $this->belongsTo(Estudiante::class,'idestudiante','idestudiante'); }
}
