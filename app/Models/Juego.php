<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Juego extends Model {
    protected $table='juegos';
    protected $primaryKey='idjuego';
    protected $fillable=['idcurso','idtipojuego','titulo','config'];
    protected $casts=['config'=>'array'];
    public function curso(){ return $this->belongsTo(Curso::class,'idcurso','idcurso'); }
    public function tipo(){ return $this->belongsTo(TipoJuego::class,'idtipojuego','idtipojuego'); }
  }