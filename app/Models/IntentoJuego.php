<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentoJuego extends Model
{
    protected $table = 'intentos_juego';
    protected $primaryKey = 'idintento';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idestudiante',
        'idcursojuego',  // 🔹 Apunta a la instancia del juego en el curso
        'puntaje',
        'aciertos',
        'errores',
        'tiempo',
        'nivel_superado',
        'detalles',       // ✅ JSON flexible (según tipo de juego)
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'detalles' => 'array',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    /** 👩‍🎓 Estudiante que realizó el intento */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    /** 🎯 Juego asignado a la unidad (instancia personalizada del curso) */
    public function cursoJuego()
    {
        return $this->belongsTo(CursoJuego::class, 'idcursojuego', 'idcursojuego');
    }

    /** 🎮 Juego base (catálogo principal) */
    public function juego()
    {
        return $this->hasOneThrough(
            Juego::class,        // Modelo final
            CursoJuego::class,   // Modelo intermedio
            'idcursojuego',      // Foreign key en CursoJuego (relación con IntentoJuego)
            'idjuego',           // Foreign key en Juego
            'idcursojuego',      // Local key en IntentoJuego
            'idjuego'            // Local key en CursoJuego
        );
    }

    /* ───────────────────────────────
     * ⚙️ EVENTOS AUTOMÁTICOS
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::created(function ($intento) {
            // Solo actualizar progreso si el estudiante aprobó el juego (puntaje >= 70)
            if ($intento->puntaje >= 70) {
                $matricula = \App\Models\Matricula::where('idestudiante', $intento->idestudiante)
                    ->where('estado', 'activa')
                    ->first();

                if ($matricula) {
                    $matricula->actualizarProgresoCurso();
                }
            }
        });
    }
}
