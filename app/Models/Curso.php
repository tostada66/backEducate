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

    // Incluir automáticamente la duración total en JSON
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

    // Curso → Clases (a través de Unidades)
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

    // 📦 Oferta asociada
    public function oferta()
    {
        return $this->hasOne(Oferta::class, 'idcurso', 'idcurso');
    }

    // 📜 Licencia (cuando se acepta la oferta)
    public function licencia()
    {
        return $this->hasOne(Licencia::class, 'idcurso', 'idcurso');
    }

    // 🗒️ Observaciones
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
            // Solo si realmente cambió el estado
            if ($curso->wasChanged('estado')) {
                $nuevoEstado = $curso->estado;

                // 🔹 Obtener IDs de unidades
                $idsUnidades = \App\Models\Unidad::where('idcurso', $curso->idcurso)
                    ->pluck('idunidad');

                if ($idsUnidades->isNotEmpty()) {
                    // 🔁 Actualizar unidades
                    \App\Models\Unidad::whereIn('idunidad', $idsUnidades)
                        ->update(['estado' => $nuevoEstado]);

                    // 🔹 Obtener IDs de clases
                    $idsClases = \App\Models\Clase::whereIn('idunidad', $idsUnidades)
                        ->pluck('idclase');

                    if ($idsClases->isNotEmpty()) {
                        // 🔁 Actualizar clases y contenidos
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
