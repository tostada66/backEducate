<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';
    protected $primaryKey = 'idsus';
    protected $fillable = ['idestudiante','idplan','fecha_inicio','fecha_fin','estado'];
    protected $casts = ['estado'=>'boolean','fecha_inicio'=>'date','fecha_fin'=>'date'];

    public function estudiante(){ return $this->belongsTo(Estudiante::class,'idestudiante','idestudiante'); }
    public function plan(){ return $this->belongsTo(TipoPlan::class,'idplan','idplan'); }
}
