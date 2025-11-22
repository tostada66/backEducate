<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Contenido extends Model
{
    use SoftDeletes;

    protected $table = 'contenidos';
    protected $primaryKey = 'idcontenido';
    public $timestamps = true;

    protected $fillable = [
        'idclase',
        'titulo',
        'descripcion',
        'tipo',
        'url',
        'miniatura',
        'duracion',

        'thumb_vtt',
        'thumb_sprite_w',
        'thumb_sprite_h',

        'ancho',
        'alto',
        'fps',
        'bitrate_kbps',
        'codec_video',
        'codec_audio',
        'mime_type',
        'size_bytes',

        'estado_proceso',
        'procesado_en',
        'error_proceso',

        'storage_driver',
        'hash_archivo',

        'orden',
        'estado',
    ];

    protected $casts = [
        'duracion'        => 'integer',
        'thumb_sprite_w'  => 'integer',
        'thumb_sprite_h'  => 'integer',
        'ancho'           => 'integer',
        'alto'            => 'integer',
        'fps'             => 'decimal:3',
        'bitrate_kbps'    => 'integer',
        'size_bytes'      => 'integer',
        'procesado_en'    => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    protected $appends = [
        'url_publica',
        'miniatura_publica',
        'thumb_vtt_publica',
    ];

    /* ============================================================
     * 🔗 RELACIONES
     * ============================================================*/

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }

    public function vistas()
    {
        return $this->hasMany(ClaseVista::class, 'idcontenido', 'idcontenido');
    }

    /* ============================================================
     * 🔍 SCOPES (ya tipados, sin warnings)
     * ============================================================*/

    public function scopeVideo(Builder $query)
    {
        return $query->where('tipo', 'video');
    }

    public function scopeActivos(Builder $query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopePublicados(Builder $query)
    {
        return $query->where('estado', 'publicado');
    }

    /* ============================================================
     * 🔁 EVENTOS PARA RE-CÁLCULO DE DURACIONES
     * ============================================================*/

    protected static function booted()
    {
        static::saved(function (Contenido $contenido) {
            if ($contenido->tipo === 'video') {
                $contenido->sincronizarDuraciones();
            }
        });

        static::deleted(function (Contenido $contenido) {
            if ($contenido->tipo === 'video') {
                $contenido->sincronizarDuraciones();
            }
        });
    }

    /* ============================================================
     * 🧠 SINCRONIZAR DURACIONES HACIA ARRIBA
     * ============================================================*/

    public function sincronizarDuraciones()
    {
        /** -------------------------
         * 1️⃣ Clase
         * -------------------------*/
        $clase = $this->clase;
        if (!$clase) return;

        $clase->duracion_total = $clase
            ->contenidos()
            ->where('tipo', 'video')
            ->sum('duracion');

        $clase->saveQuietly();

        /** -------------------------
         * 2️⃣ Unidad
         * -------------------------*/
        $unidad = $clase->unidad;
        if (!$unidad) return;

        $unidad->duracion_total = $unidad
            ->clases()
            ->sum('duracion_total');

        $unidad->saveQuietly();

        /** -------------------------
         * 3️⃣ Curso
         * -------------------------*/
        $curso = $unidad->curso;
        if (!$curso) return;

        $curso->duracion_total = $curso
            ->unidades()
            ->sum('duracion_total');

        $curso->saveQuietly();
    }

    /* ============================================================
     * 🌐 ACCESSORS
     * ============================================================*/

    public function getUrlPublicaAttribute()
    {
        if (!$this->url) return null;

        return filter_var($this->url, FILTER_VALIDATE_URL)
            ? $this->url
            : asset('storage/' . ltrim($this->url, '/'));
    }

    public function getMiniaturaPublicaAttribute()
    {
        if (!$this->miniatura) return null;

        return filter_var($this->miniatura, FILTER_VALIDATE_URL)
            ? $this->miniatura
            : asset('storage/' . ltrim($this->miniatura, '/'));
    }

    public function getThumbVttPublicaAttribute()
    {
        if (!$this->thumb_vtt) return null;

        return filter_var($this->thumb_vtt, FILTER_VALIDATE_URL)
            ? $this->thumb_vtt
            : asset('storage/' . ltrim($this->thumb_vtt, '/'));
    }
}
