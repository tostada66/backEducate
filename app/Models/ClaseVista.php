<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProgresoClase;

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
     * 📊 Aplica una muestra de progreso de forma MONÓTONA (nunca baja).
     *
     * Reglas de “aplicación” (anti-ruido):
     *  - Si supera un delta mínimo de segundos desde el máximo histórico, o
     *  - Si cruza un checkpoint (%): 25, 50, 75 (y 60 para completado), o
     *  - Si llega prácticamente al final del video.
     *
     * @param int   $current      Segundo actual reportado por el cliente
     * @param int   $duration     Duración total del video (≥1)
     * @param int   $umbral       % para marcar completado (default 60)
     * @param int   $minDeltaS    Delta mínimo en segundos para aplicar (default 300s)
     * @param array $checkpoints  % en los que conviene guardar (default [25,50,75,100])
     * @return self
     */
    public function applySample(
        int $current,
        int $duration,
        int $umbral = 60,
        int $minDeltaS = 300,
        array $checkpoints = [25, 50, 75, 100]
    ): self {
        $duration = max(1, $duration);
        $current  = max(0, min($current, $duration));

        // Estado previo (histórico)
        $prevMaxSeg   = (int) ($this->ultimo_segundo ?? 0);
        $prevVistos   = (int) ($this->segundos_vistos ?? 0);
        $prevPct      = (int) ($this->porcentaje ?? 0);
        $prevComplete = (bool) ($this->completado ?? false);

        // Propuesta desde la muestra (sin aplicar aún)
        $avanceSeg    = max(0, $current - $prevMaxSeg);               // retroceder no suma
        $candMaxSeg   = max($prevMaxSeg, $current);                   // nuevo máximo de segundo
        $candVistos   = min($duration, max($prevVistos, $prevMaxSeg + $avanceSeg));
        $candPct      = max($prevPct, (int) floor(($candVistos / $duration) * 100));
        $llegoFinal   = $current >= ($duration - 1);

        // Delta efectivo para videos cortos (< 5 min)
        $effDeltaS = ($duration < 300) ? 60 : $minDeltaS;

        // ¿Cruza algún checkpoint relevante?
        $cruzaCheckpoint = false;
        // nos aseguramos de incluir 60 en la lógica (por si no está en $checkpoints)
        $todosCheckpoints = array_unique(array_merge($checkpoints, [$umbral]));
        sort($todosCheckpoints);
        foreach ($todosCheckpoints as $cp) {
            if ($prevPct < $cp && $candPct >= $cp) {
                $cruzaCheckpoint = true;
                break;
            }
        }

        // ¿Supera delta de segundos?
        $superaDelta = ($candMaxSeg - $prevMaxSeg) >= $effDeltaS;

        // ¿Cruza explícitamente el umbral de completado?
        $cruzaUmbral = ($prevPct < $umbral && $candPct >= $umbral);

        // Si no cumple ninguna condición de aplicación, NO modificamos (evita “bajar” o ruido)
        if (!($superaDelta || $cruzaCheckpoint || $cruzaUmbral || $llegoFinal)) {
            return $this;
        }

        // ✅ Aplicación monótona
        $this->ultimo_segundo  = $candMaxSeg;
        $this->segundos_vistos = $candVistos;
        $this->porcentaje      = min(100, $candPct);
        $this->completado      = $prevComplete || $candPct >= $umbral || $llegoFinal;

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
