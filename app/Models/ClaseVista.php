<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProgresoClase; // 👈 Importamos el modelo

class ClaseVista extends Model
{
    /** 📦 Tabla y PK personalizada */
    protected $table = 'clase_vistas';
    protected $primaryKey = 'idvistaclase';
    public $timestamps = true;

    /** Campos asignables */
    protected $fillable = [
        'idclase',
        'idcontenido',
        'idestudiante',
        'idmatricula',
        'ultimo_segundo',
        'segundos_vistos',
        'porcentaje',
        'completado',
    ];

    /** Casts automáticos */
    protected $casts = [
        'ultimo_segundo'  => 'integer',
        'segundos_vistos' => 'integer',
        'porcentaje'      => 'integer',
        'completado'      => 'boolean',
    ];

    /* ─────────────── RELACIONES ─────────────── */
    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }

    public function contenido()
    {
        return $this->belongsTo(Contenido::class, 'idcontenido', 'idcontenido');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'idmatricula', 'idmatricula');
    }

    /* ─────────────── SCOPES ─────────────── */
    public function scopeDeEstudiante($query, int $idestudiante)
    {
        return $query->where('idestudiante', $idestudiante);
    }

    public function scopeDeClase($query, int $idclase)
    {
        return $query->where('idclase', $idclase);
    }

    public function scopeCompletados($query)
    {
        return $query->where('completado', true);
    }

    public function scopeIncompletos($query)
    {
        return $query->where('completado', false);
    }

    public function scopeConPorcentajeMin($query, int $min = 60)
    {
        return $query->where('porcentaje', '>=', $min);
    }

    /* ─────────────── HELPERS ─────────────── */

    /**
     * 📊 Actualiza el progreso del video según el segundo actual.
     */
    public function applySample(int $current, int $duration, int $umbral = 60): self
    {
        $duration = max(1, $duration);
        $current  = max(0, min($current, $duration));

        $prevMax    = (int) $this->ultimo_segundo;
        $prevVistos = (int) $this->segundos_vistos;

        $delta            = max(0, $current - $prevMax);
        $segundos_vistos  = min($duration, $prevVistos + $delta);
        $ultimo_segundo   = max($prevMax, $current);
        $porcentaje       = (int) floor(($segundos_vistos / $duration) * 100);
        $completado       = $porcentaje >= $umbral;

        $this->ultimo_segundo  = $ultimo_segundo;
        $this->segundos_vistos = $segundos_vistos;
        $this->porcentaje      = min(100, $porcentaje);
        $this->completado      = $completado;

        return $this;
    }

    /**
     * 💾 Guarda los cambios y sincroniza con progreso_clases si el video se completa.
     */
    public function syncAndSave(): bool
    {
        $saved = false;

        if ($this->isDirty()) {
            $saved = $this->save();
        }

        // 🧩 Si el video se completó, marcar la clase como completada también
        if ($saved && $this->completado && $this->idmatricula && $this->idclase) {
            ProgresoClase::updateOrCreate(
                [
                    'idmatricula' => $this->idmatricula,
                    'idclase'     => $this->idclase,
                ],
                [
                    'completado'      => true,
                    'progreso'        => 100,
                    'ultima_vista_at' => now(),
                ]
            );
        }

        return $saved;
    }
}
