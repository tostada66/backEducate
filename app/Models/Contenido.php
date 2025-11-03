<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'tipo',        // video, imagen, documento, etc.
        'url',         // ruta en storage o URL externa
        'miniatura',   // opcional, si es video
        'duracion',    // duración del video en segundos
        'orden',
        'estado',      // borrador, publicado, etc.
    ];

    protected $dates = ['deleted_at'];
    protected $appends = ['url_publica', 'miniatura_publica'];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */
    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }

    /** Progresos (vistas de video) asociados a este contenido */
    public function vistas()
    {
        return $this->hasMany(ClaseVista::class, 'idcontenido', 'idcontenido');
    }

    /* ───────────────────────────────
     * 🔍 SCOPES
     * ─────────────────────────────── */

    /** Solo contenidos tipo video */
    public function scopeVideo($query)
    {
        return $query->where('tipo', 'video');
    }

    /** Solo los contenidos publicados */
    public function scopePublicados($query)
    {
        return $query->where('estado', 'publicado');
    }

    /* ───────────────────────────────
     * 🌐 ACCESSORS (URLs públicas)
     * ─────────────────────────────── */

    /** Devuelve la URL pública del contenido (archivo o enlace externo) */
    public function getUrlPublicaAttribute()
    {
        if (!$this->url) {
            return null;
        }

        // Si no es URL absoluta, construir desde /storage
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            return asset('storage/' . ltrim($this->url, '/'));
        }

        return $this->url;
    }

    /** Devuelve la miniatura pública del video (si existe) */
    public function getMiniaturaPublicaAttribute()
    {
        if (!$this->miniatura) {
            return null;
        }

        if (!filter_var($this->miniatura, FILTER_VALIDATE_URL)) {
            return asset('storage/' . ltrim($this->miniatura, '/'));
        }

        return $this->miniatura;
    }

    /* ───────────────────────────────
     * ⚠️ NOTA IMPORTANTE
     * ───────────────────────────────
     * No sincronices el estado hacia Clase aquí.
     * Si quieres actualizar el estado de la clase
     * cuando se publique el video, usa un Observer.
     */
}
