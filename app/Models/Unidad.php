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

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function clases()
    {
        return $this->hasMany(Clase::class, 'idunidad', 'idunidad');
    }

    /* ───────────────────────────────
     * ⏱️ ACCESSOR: Duración total
     * ─────────────────────────────── */
    public function getDuracionTotalAttribute()
    {
        if ($this->relationLoaded('clases')) {
            return $this->clases->sum(fn($clase) => $clase->duracion_total);
        }

        return $this->clases()
            ->with('contenidos')
            ->get()
            ->sum(fn($clase) => $clase->duracion_total);
    }

    /* ───────────────────────────────
     * 🔁 SINCRONIZAR ESTADOS AUTOMÁTICAMENTE
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::updated(function ($unidad) {
            // Solo si realmente cambió el estado
            if ($unidad->wasChanged('estado')) {
                $nuevoEstado = $unidad->estado;

                // 🔹 Obtener clases asociadas
                $idsClases = \App\Models\Clase::where('idunidad', $unidad->idunidad)
                    ->pluck('idclase');

                if ($idsClases->isNotEmpty()) {
                    // 🔁 Actualizar clases
                    \App\Models\Clase::whereIn('idclase', $idsClases)
                        ->update(['estado' => $nuevoEstado]);

                    // 🔁 Actualizar contenidos
                    \App\Models\Contenido::whereIn('idclase', $idsClases)
                        ->update(['estado' => $nuevoEstado]);
                }
            }
        });
    }
}
