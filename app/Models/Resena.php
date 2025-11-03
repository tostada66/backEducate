<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    use HasFactory;

    protected $table = 'resenas';
    protected $primaryKey = 'idresena';

    protected $fillable = [
        'idcurso',
        'idestudiante',
        'puntuacion',
        'comentario',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    /* ───────────────────────────────
     * 🔁 EVENTOS AUTOMÁTICOS
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::created(function ($resena) {
            $resena->actualizarPromedioCurso();
        });

        static::updated(function ($resena) {
            $resena->actualizarPromedioCurso();
        });

        static::deleted(function ($resena) {
            $resena->actualizarPromedioCurso();
        });
    }

    /* ───────────────────────────────
     * ⭐ MÉTODO: Actualizar promedio del curso
     * ─────────────────────────────── */
    public function actualizarPromedioCurso()
    {
        $curso = $this->curso;

        if ($curso) {
            $stats = self::where('idcurso', $curso->idcurso)
                ->selectRaw('AVG(puntuacion) as promedio, COUNT(*) as total')
                ->first();

            $curso->update([
                'promedio_resenas' => round($stats->promedio ?? 0, 2),
                'total_resenas' => $stats->total ?? 0,
            ]);
        }
    }
}
