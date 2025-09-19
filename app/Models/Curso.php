<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use SoftDeletes; // 👈 habilita borrado lógico con deleted_at

    protected $table = 'cursos';
    protected $primaryKey = 'idcurso';

    protected $fillable = [
        'idprofesor',
        'nombre',
        'slug',
        'descripcion',
        'nivel',
        'imagen',
        'duracion_estimada',
        'numero_clases',
        'estado',
        'fecha_creacion'
    ];

    protected $dates = ['deleted_at']; // 👈 para que Laravel maneje bien el campo

    // Relaciones
    public function profesor() {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }

    public function clases() {
        return $this->hasMany(Clase::class, 'idcurso', 'idcurso');
    }

    public function categorias() {
        return $this->belongsToMany(Categoria::class, 'curso_categoria', 'idcurso', 'idcategoria');
    }

    public function reviews() {
        return $this->hasMany(Review::class, 'idcurso', 'idcurso');
    }

    public function examenes() {
        return $this->hasMany(Examen::class, 'idcurso', 'idcurso');
    }

    public function juegos() {
        return $this->hasMany(Juego::class, 'idcurso', 'idcurso');
    }
}
