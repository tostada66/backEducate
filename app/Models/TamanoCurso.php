<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamanoCurso extends Model
{
    protected $table='tamano_cursos';
    protected $primaryKey='idtamano';
    protected $fillable=['nombre','descripcion'];
}
