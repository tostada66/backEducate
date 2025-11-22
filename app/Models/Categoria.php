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
            'idcategoria',   // FK de Categoria en pivot
            'idestudiante'   // FK de Estudiante en pivot
        )
        ->withTimestamps()
        ->select('estudiantes.idestudiante', 'estudiantes.idusuario', 'estudiantes.nivelacademico');
    }

    // 🔹 Relación uno a muchos con cursos
    public function cursos()
    {
        return $this->hasMany(Curso::class, 'idcategoria', 'idcategoria');
    }
}
