<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

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
        'estado',
        'duracion_total', // ⚠️ NUEVO: campo físico en la DB
    ];

    protected $casts = [
        'duracion_total' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['miniatura_publica'];

    /* ============================================================
     * 🔗 RELACIONES
     * ==========================================================*/

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'idclase', 'idclase')
                    ->orderBy('orden');
    }

    public function video()
    {
        return $this->hasOne(Contenido::class, 'idclase', 'idclase')
            ->where('tipo', 'video');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'idclase', 'idclase')
                    ->whereNull('idpadre')
                    ->with(['usuario', 'respuestas.usuario'])
                    ->orderByDesc('created_at');
    }

    public function vistas()
    {
        return $this->hasMany(ClaseVista::class, 'idclase', 'idclase');
    }

    /* ============================================================
     * ⏱️ ACCESSORS
     * ==========================================================*/

    /** Miniatura del video principal */
    public function getMiniaturaPublicaAttribute()
    {
        $video = $this->relationLoaded('video') ? $this->video : $this->video()->first();
        return $video?->miniatura_publica;
    }

    /* ============================================================
     * 🔁 SINCRONIZACIÓN DE DURACIONES
     * ==========================================================*/

    /** Recalcular duración cuando un Contenido cambia */
    public function recalcularDuracion()
    {
        // 1️⃣ Recalcular duración de la clase
        $this->duracion_total = $this->contenidos()
            ->where('tipo', 'video')
            ->sum('duracion');

        $this->saveQuietly();

        // 2️⃣ Recalcular duración de la unidad
        $unidad = $this->unidad;
        if ($unidad) {
            $unidad->duracion_total = $unidad->clases()->sum('duracion_total');
            $unidad->saveQuietly();

            // 3️⃣ Recalcular duración del curso
            $curso = $unidad->curso;
            if ($curso) {
                $curso->duracion_total = $curso->unidades()->sum('duracion_total');
                $curso->saveQuietly();
            }
        }
    }

    /* ============================================================
     * EVENTS → se ejecuta cuando se guarda o elimina la clase
     * ==========================================================*/

    protected static function booted()
    {
        static::saved(function (Clase $clase) {
            $clase->recalcularDuracion();
        });

        static::deleted(function (Clase $clase) {
            $clase->recalcularDuracion();
        });
    }

    /* ============================================================
     * SCOPES SIN WARNINGS
     * ==========================================================*/

    public function scopeWithVideo(Builder $query)
    {
        return $query->with('video');
    }

    public function scopeWithVistaDe(Builder $query, int $idestudiante)
    {
        return $query->with(['vistas' => fn (Builder $q) => $q->where('idestudiante', $idestudiante)]);
    }
}
