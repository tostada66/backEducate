<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use SoftDeletes;

    protected $table = 'cursos';
    protected $primaryKey = 'idcurso';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idprofesor',
        'idcategoria',
        'nombre',
        'slug',
        'descripcion',
        'nivel',
        'imagen',
        'estado',
        'promedio_resenas',
        'total_resenas',
    ];

    protected $appends = ['duracion_total'];

    /* ───────────────────────────────
     * 🔗 RELACIONES PRINCIPALES
     * ─────────────────────────────── */

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

    public function clases()
    {
        return $this->hasManyThrough(
            Clase::class,
            Unidad::class,
            'idcurso',
            'idunidad',
            'idcurso',
            'idunidad'
        );
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'idcurso', 'idcurso');
    }

    public function examenes()
    {
        return $this->hasMany(Examen::class, 'idcurso', 'idcurso');
    }

    // 🎮 Nuevo: Juegos a través de las unidades
    public function juegos()
    {
        return $this->hasManyThrough(
            CursoJuego::class,
            Unidad::class,
            'idcurso',       // Foreign key en 'unidades'
            'idunidad',      // Foreign key en 'curso_juego'
            'idcurso',       // Local key en 'cursos'
            'idunidad'       // Local key en 'unidades'
        );
    }

    // 👇 Eliminadas las relaciones directas que usaban idcurso:
    // public function cursoJuegos() {...}
    // public function juegos() {...} ← reemplazada por la nueva versión

    public function oferta()
    {
        return $this->hasOne(Oferta::class, 'idcurso', 'idcurso');
    }

    public function licencia()
    {
        return $this->hasOne(Licencia::class, 'idcurso', 'idcurso');
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class, 'idcurso', 'idcurso');
    }

    /* ───────────────────────────────
     * ⏱️ ACCESSOR: Duración total
     * ─────────────────────────────── */
    public function getDuracionTotalAttribute()
    {
        if ($this->relationLoaded('unidades')) {
            return $this->unidades->sum(fn($unidad) => $unidad->duracion_total);
        }

        return $this->unidades()
            ->with('clases.contenidos')
            ->get()
            ->sum(fn($unidad) => $unidad->duracion_total);
    }

    /* ───────────────────────────────
     * 🔁 SINCRONIZAR ESTADOS AUTOMÁTICAMENTE
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::updated(function ($curso) {
            if ($curso->wasChanged('estado')) {
                $nuevoEstado = $curso->estado;

                $idsUnidades = \App\Models\Unidad::where('idcurso', $curso->idcurso)
                    ->pluck('idunidad');

                if ($idsUnidades->isNotEmpty()) {
                    \App\Models\Unidad::whereIn('idunidad', $idsUnidades)
                        ->update(['estado' => $nuevoEstado]);

                    $idsClases = \App\Models\Clase::whereIn('idunidad', $idsUnidades)
                        ->pluck('idclase');

                    if ($idsClases->isNotEmpty()) {
                        \App\Models\Clase::whereIn('idclase', $idsClases)
                            ->update(['estado' => $nuevoEstado]);

                        \App\Models\Contenido::whereIn('idclase', $idsClases)
                            ->update(['estado' => $nuevoEstado]);
                    }
                }
            }
        });
    }
}
