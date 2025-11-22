<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

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
        'estado',
        'duracion_total', // ⚠️ NUEVO: duración física acumulada
    ];

    protected $casts = [
        'duracion_total' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /* ============================================================
     * 🔗 RELACIONES
     * ==========================================================*/

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function clases()
    {
        return $this->hasMany(Clase::class, 'idunidad', 'idunidad')
                    ->orderBy('orden');
    }

    public function examen()
    {
        return $this->hasOne(Examen::class, 'idunidad', 'idunidad');
    }

    public function examenes()
    {
        return $this->hasMany(Examen::class, 'idunidad', 'idunidad');
    }

    public function juegos()
    {
        return $this->hasMany(CursoJuego::class, 'idunidad', 'idunidad');
    }

    /* ============================================================
     * 🔁 RECÁLCULO AUTOMÁTICO DE DURACIONES
     * ==========================================================*/

    public function recalcularDuracion()
    {
        // 1️⃣ Duración de la unidad = suma de clases
        $this->duracion_total = $this->clases()->sum('duracion_total');
        $this->saveQuietly();

        // 2️⃣ Recalcular duración del curso
        $curso = $this->curso;
        if ($curso) {
            $curso->duracion_total = $curso->unidades()->sum('duracion_total');
            $curso->saveQuietly();
        }
    }

    /* ============================================================
     * EVENTOS
     * ==========================================================*/

    protected static function booted()
    {
        // ▶ Se recalcula cuando se modifica o elimina la unidad
        static::saved(function (Unidad $unidad) {
            $unidad->recalcularDuracion();
        });

        static::deleted(function (Unidad $unidad) {
            $unidad->recalcularDuracion();
        });

        // ▶ Cuando cambia el estado, afectamos a clases y contenidos
        static::updated(function (Unidad $unidad) {
            if ($unidad->wasChanged('estado')) {
                $nuevoEstado = $unidad->estado;

                // Clases
                $idsClases = Clase::where('idunidad', $unidad->idunidad)->pluck('idclase');

                if ($idsClases->isNotEmpty()) {
                    Clase::whereIn('idclase', $idsClases)->update(['estado' => $nuevoEstado]);
                    Contenido::whereIn('idclase', $idsClases)->update(['estado' => $nuevoEstado]);
                }
            }
        });
    }

    /* ============================================================
     * SCOPES (SIN WARNINGS)
     * ==========================================================*/

    public function scopeActivas(Builder $query)
    {
        return $query->where('estado', 'publicado');
    }
}
