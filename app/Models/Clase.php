<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clase extends Model
{
    use SoftDeletes;

    protected $table = 'clases';
    protected $primaryKey = 'idclase';
    public $timestamps = true;

    protected $fillable = [
        'idunidad',
        'titulo',
        'descripcion',
        'orden',
        'estado', // borrador, publicado, etc.
    ];

    protected $dates = ['deleted_at'];
    protected $appends = ['duracion_total', 'miniatura_publica'];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    /** Unidad a la que pertenece */
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    /** Todos los contenidos de la clase */
    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'idclase', 'idclase')->orderBy('orden');
    }

    /** El único video principal de la clase */
    public function video()
    {
        return $this->hasOne(Contenido::class, 'idclase', 'idclase')
            ->where('tipo', 'video');
    }

    /** Comentarios (raíz y respuestas anidadas) */
    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'idclase', 'idclase')
                    ->whereNull('idpadre')
                    ->with(['usuario', 'respuestas.usuario'])
                    ->orderByDesc('created_at');
    }

    /** Progresos (vistas) de estudiantes */
    public function vistas()
    {
        return $this->hasMany(ClaseVista::class, 'idclase', 'idclase');
    }

    /* ───────────────────────────────
     * ⏱️ ACCESSORS
     * ─────────────────────────────── */

    /** Suma de duración total de los videos (por ahora 1 video por clase) */
    public function getDuracionTotalAttribute()
    {
        if ($this->relationLoaded('contenidos')) {
            return $this->contenidos
                ->where('tipo', 'video')
                ->sum('duracion') ?? 0;
        }

        return $this->contenidos()
            ->where('tipo', 'video')
            ->sum('duracion') ?? 0;
    }

    /** Miniatura pública del video principal */
    public function getMiniaturaPublicaAttribute()
    {
        $video = $this->relationLoaded('video')
            ? $this->video
            : $this->video()->first();

        return $video?->miniatura_publica;
    }

    /* ───────────────────────────────
     * 🧠 HELPERS / SCOPES
     * ─────────────────────────────── */

    /** Devuelve el progreso (vista) del estudiante actual */
    public function vistaDeEstudiante(int $idestudiante): ?ClaseVista
    {
        return $this->vistas()
            ->where('idestudiante', $idestudiante)
            ->first();
    }

    /** Cargar clase con su video */
    public function scopeWithVideo($query)
    {
        return $query->with('video');
    }

    /** Cargar clase con vista del estudiante */
    public function scopeWithVistaDe($query, int $idestudiante)
    {
        return $query->with(['vistas' => fn($q) => $q->where('idestudiante', $idestudiante)]);
    }
}
