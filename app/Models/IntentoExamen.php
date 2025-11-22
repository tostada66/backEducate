<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoExamen extends Model
{
    use HasFactory;

    protected $table = 'intento_examens';
    protected $primaryKey = 'idintento';

    protected $fillable = [
        'idexamen',
        'idestudiante',
        'vidas_restantes',
        'puntaje',
        'aprobado',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'aprobado' => 'boolean',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    /** Examen al que pertenece este intento */
    public function examen()
    {
        return $this->belongsTo(Examen::class, 'idexamen', 'idexamen');
    }

    /** Estudiante que realizó el intento */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    /** Respuestas asociadas al intento */
    public function respuestas()
    {
        return $this->hasMany(IntentoRespuesta::class, 'idintento', 'idintento');
    }

    /* ───────────────────────────────
     * ⚙️ EVENTO AUTOMÁTICO DE PROGRESO
     * ─────────────────────────────── */

    protected static function booted()
    {
        static::saved(function ($intento) {
            // ✅ Solo si el intento fue aprobado
            if ($intento->aprobado && $intento->examen && $intento->estudiante) {
                // Buscar la matrícula activa del estudiante para el curso de ese examen
                $idcurso = $intento->examen->unidad->idcurso ?? null;

                if ($idcurso) {
                    $matricula = \App\Models\Matricula::where('idestudiante', $intento->idestudiante)
                        ->where('idcurso', $idcurso)
                        ->where('estado', 'activa')
                        ->first();

                    // Si hay matrícula activa, actualizamos el progreso global
                    if ($matricula) {
                        $matricula->actualizarProgresoCurso();
                    }
                }
            }
        });
    }
}
