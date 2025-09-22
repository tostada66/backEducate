<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use SoftDeletes;

    protected $table = 'cursos';
    protected $primaryKey = 'idcurso';

    protected $fillable = [
        'idprofesor',
        'idcategoria',
        'nombre',
        'slug',
        'descripcion',
        'nivel',
        'imagen',
        'estado',
    ];

    // Para incluir automáticamente la duración total en JSON
    protected $appends = ['duracion_total'];

    // 🔹 Relaciones

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'idcategoria', 'idcategoria');
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'idcurso', 'idcurso');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'idcurso', 'idcurso');
    }

    public function examenes()
    {
        return $this->hasMany(Examen::class, 'idcurso', 'idcurso');
    }

    public function juegos()
    {
        return $this->hasMany(Juego::class, 'idcurso', 'idcurso');
    }

    // 🔹 Accessor dinámico: duración total del curso
    public function getDuracionTotalAttribute()
    {
        // Si ya cargaste unidades y clases, calcula en memoria
        if ($this->relationLoaded('unidades')) {
            return $this->unidades->sum(
                fn ($unidad) => $unidad->duracion_total
            );
        }

        // Si no, carga con relaciones necesarias en una sola consulta
        return $this->unidades()
            ->with('clases.contenidos')
            ->get()
            ->sum(fn ($unidad) => $unidad->duracion_total);
    }
}
