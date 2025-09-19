<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresoClase extends Model
{
    protected $table = 'progreso_clases';
    protected $primaryKey = 'idprogreso';
    protected $fillable = ['idmatricula','idclase','completado','progreso','ultima_vista_at'];
    protected $casts = ['completado'=>'boolean','ultima_vista_at'=>'datetime'];

    public function matricula(){ return $this->belongsTo(Matricula::class,'idmatricula','idmatricula'); }
    public function clase(){ return $this->belongsTo(Clase::class,'idclase','idclase'); }
}
