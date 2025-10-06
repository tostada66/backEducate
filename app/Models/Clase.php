<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clase extends Model
{
    use SoftDeletes;

    protected $table = 'clases';
    protected $primaryKey = 'idclase';

    protected $fillable = [
        'idunidad',
        'titulo',
        'descripcion',
        'orden',
        'estado',
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['duracion_total', 'miniatura_publica'];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */
    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'idclase', 'idclase');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    /* ───────────────────────────────
     * ⏱️ ACCESSOR: Duración total
     * ─────────────────────────────── */
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

    /* ───────────────────────────────
     * 🖼️ ACCESSOR: Miniatura pública
     * ─────────────────────────────── */
    public function getMiniaturaPublicaAttribute()
    {
        $video = $this->relationLoaded('contenidos')
            ? $this->contenidos->firstWhere('tipo', 'video')
            : $this->contenidos()->where('tipo', 'video')->first();

        return $video ? $video->miniatura_publica : null;
    }

    /* ───────────────────────────────
     * 🔁 SINCRONIZAR ESTADOS AUTOMÁTICAMENTE
     * ─────────────────────────────── */
    protected static function booted()
    {
        static::updated(function ($clase) {
            // Solo si realmente cambió el estado
            if ($clase->wasChanged('estado')) {
                $nuevoEstado = $clase->estado;

                // 🔁 Actualizar todos los contenidos asociados
                \App\Models\Contenido::where('idclase', $clase->idclase)
                    ->update(['estado' => $nuevoEstado]);
            }
        });
    }
}
