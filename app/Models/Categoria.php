<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'idcategoria';
    protected $fillable = ['nombre', 'descripcion'];

    // 🔹 Relación muchos a muchos con estudiantes
    public function estudiantes()
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiante_categoria',
            'idcategoria',   // FK local en pivot
            'idestudiante'   // FK relacionada
        )
        ->withTimestamps()
        ->select('estudiantes.idestudiante', 'estudiantes.idusuario', 'estudiantes.nivelacademico');
        // 👆 Así evitas ambigüedad
    }
}
