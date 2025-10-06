<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contenido extends Model
{
    use SoftDeletes;

    protected $table = 'contenidos';
    protected $primaryKey = 'idcontenido';

    protected $fillable = [
        'idclase',
        'titulo',
        'descripcion',
        'tipo',        // video, imagen, documento, etc.
        'url',         // archivo principal
        'miniatura',   // opcional, solo si es video
        'duracion',    // en segundos o minutos (según definas)
        'orden',
        'estado'
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

    /* ───────────────────────────────
     * 🌐 ACCESSORS
     * ─────────────────────────────── */
    public function getUrlPublicaAttribute()
    {
        if ($this->url && !filter_var($this->url, FILTER_VALIDATE_URL)) {
            return asset('storage/' . ltrim($this->url, '/'));
        }
        return $this->url;
    }

    public function getMiniaturaPublicaAttribute()
    {
        if ($this->miniatura && !filter_var($this->miniatura, FILTER_VALIDATE_URL)) {
            return asset('storage/' . ltrim($this->miniatura, '/'));
        }
        return $this->miniatura;
    }

    /* ───────────────────────────────
     * 🔁 SINCRONIZAR ESTADO HACIA ARRIBA (opcional)
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::updated(function ($contenido) {
            // Solo si realmente cambió el estado
            if ($contenido->wasChanged('estado')) {
                $clase = $contenido->clase;

                // Evitar bucles infinitos: solo si el estado de la clase es distinto
                if ($clase && $clase->estado !== $contenido->estado) {
                    $clase->estado = $contenido->estado;
                    $clase->save();
                }
            }
        });
    }
}
