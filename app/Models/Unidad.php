<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidad extends Model
{
    use SoftDeletes;

    protected $table = 'unidades';
    protected $primaryKey = 'idunidad';

    protected $fillable = [
        'idcurso',
        'titulo',
        'descripcion',
        'objetivos',
        'imagen',
        'duracion_estimada',
        'estado',
    ];

    protected $appends = ['duracion_total'];

    // 🔹 Relaciones
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function clases()
    {
        return $this->hasMany(Clase::class, 'idunidad', 'idunidad');
    }

    // 🔹 Accessor dinámico: duración total de la unidad
    public function getDuracionTotalAttribute()
    {
        // Si ya cargaste las clases, calcula en memoria
        if ($this->relationLoaded('clases')) {
            return $this->clases->sum(
                fn ($clase) => $clase->duracion_total
            );
        }

        // Si no, carga con contenidos en una sola consulta
        return $this->clases()
            ->with('contenidos')
            ->get()
            ->sum(fn ($clase) => $clase->duracion_total);
    }
}
