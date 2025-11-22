<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $table = 'matriculas';
    protected $primaryKey = 'idmatricula';
    public $timestamps = true;

    protected $fillable = [
        'idestudiante',
        'idcurso',
        'fecha',
        'estado',              // activa, completada o cancelada
        'porcentaje_avance',   // porcentaje global del curso (0–100)
    ];

    protected $casts = [
        'fecha' => 'date',
        'porcentaje_avance' => 'decimal:2',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    /** 👤 Estudiante matriculado */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    /** 📘 Curso correspondiente */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    /** 🎥 Vistas o progresos por clase (videos) */
    public function vistasClase()
    {
        return $this->hasMany(ClaseVista::class, 'idmatricula', 'idmatricula');
    }

    /* ───────────────────────────────
     * 📊 CÁLCULOS DE PROGRESO
     * ─────────────────────────────── */

    /**
     * 🔹 Calcula el porcentaje de avance actual (sin guardar).
     * Combina clases completadas + exámenes aprobados + juegos aprobados.
     */
    public function getPorcentajeAvanceAttribute()
    {
        // Asegurar que el curso esté cargado con unidades, exámenes y juegos
        if (!$this->relationLoaded('curso')) {
            $this->load('curso.unidades.clases', 'curso.unidades.examenes', 'curso.unidades.juegos');
        }

        // Totales
        $totalClases   = $this->curso?->unidades?->flatMap->clases->count() ?? 0;
        $totalExamenes = $this->curso?->unidades?->flatMap->examenes->count() ?? 0;
        $totalJuegos   = $this->curso?->unidades?->flatMap->juegos->count() ?? 0;
        $totalElementos = $totalClases + $totalExamenes + $totalJuegos;

        if ($totalElementos === 0) {
            return 0;
        }

        // ✅ Clases completadas
        $clasesCompletadas = $this->vistasClase()
            ->where('completado', true)
            ->count();

        // ✅ Exámenes aprobados
        $examenesAprobados = \App\Models\IntentoExamen::where('idestudiante', $this->idestudiante)
            ->where('aprobado', true)
            ->whereIn('idexamen', $this->curso->unidades->flatMap->examenes->pluck('idexamen'))
            ->count();

        // ✅ Juegos aprobados (puntaje >= 70)
        $juegosAprobados = \App\Models\IntentoJuego::where('idestudiante', $this->idestudiante)
            ->where('puntaje', '>=', 70)
            ->whereIn('idcursojuego', $this->curso->unidades->flatMap->juegos->pluck('idcursojuego'))
            ->distinct('idcursojuego') // cuenta solo una vez cada juego
            ->count('idcursojuego');

        // 📊 Total completados
        $totalCompletados = $clasesCompletadas + $examenesAprobados + $juegosAprobados;

        return round(($totalCompletados / $totalElementos) * 100, 2);
    }

    /**
     * 🔹 Actualiza y guarda el porcentaje global del curso.
     */
    public function actualizarProgresoCurso()
    {
        $porcentaje = $this->getPorcentajeAvanceAttribute();
        $this->porcentaje_avance = min(max($porcentaje, 0), 100); // asegura entre 0 y 100
        $this->save();
    }

    /**
     * 🔹 Indica si el curso ya está completado (100%)
     */
    public function getCompletadoAttribute()
    {
        return $this->porcentaje_avance >= 100;
    }

    /* ───────────────────────────────
     * 🔍 SCOPES
     * ─────────────────────────────── */

    /** Filtrar matrículas activas */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    /** Filtrar por curso */
    public function scopeDelCurso($query, $idcurso)
    {
        return $query->where('idcurso', $idcurso);
    }
}
