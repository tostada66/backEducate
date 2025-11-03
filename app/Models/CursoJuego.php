<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CursoJuego extends Model
{
    protected $table = 'curso_juego';
    protected $primaryKey = 'idcursojuego';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idunidad',           // 🔗 Relación con la unidad
        'idjuego',
        'nombre_tema',
        'nivel',
        'imagen',             // 🖼️ Portada personalizada
        'activo',
        'fecha_baja',         // 📅 Fecha de baja
        'fecha_eliminacion',  // 🕓 Fecha programada para eliminación
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_baja' => 'datetime',
        'fecha_eliminacion' => 'datetime',
    ];

    // 🚀 Precargar relaciones por defecto
    protected $with = ['juego', 'unidad.curso.categoria'];

    protected $appends = ['imagen_url'];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    // 🎮 Juego base (ej. "Mecanografía", "Cartas de Memoria", "Reciclaje")
    public function juego()
    {
        return $this->belongsTo(Juego::class, 'idjuego', 'idjuego');
    }

    // 📘 Unidad a la que pertenece esta instancia de juego
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    // 👨‍🎓 Intentos de estudiantes
    public function intentos()
    {
        return $this->hasMany(IntentoJuego::class, 'idcursojuego', 'idcursojuego');
    }

    // ⌨️ Palabras (para el juego de mecanografía)
    public function mecanografiaPalabras()
    {
        return $this->hasMany(JuegoMecanografiaPalabra::class, 'idcursojuego', 'idcursojuego');
    }

    // 🃏 Cartas (para el juego de memoria)
    public function cartas()
    {
        return $this->hasMany(JuegoCartasPar::class, 'idcursojuego', 'idcursojuego');
    }

    // ♻️ Ítems de reciclaje (para el juego de reciclaje)
    public function reciclajeItems()
    {
        return $this->hasMany(JuegoReciclajeItem::class, 'idcursojuego', 'idcursojuego');
    }

    /* ───────────────────────────────
     * 🖼️ ACCESOR: URL completa de la imagen
     * ─────────────────────────────── */
    public function getImagenUrlAttribute()
    {
        return $this->imagen
            ? asset('storage/' . ltrim($this->imagen, '/'))
            : null;
    }

    /* ───────────────────────────────
     * 🔍 SCOPES Y HELPERS
     * ─────────────────────────────── */

    // 🔹 Solo activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // 🔹 Solo inactivos (dados de baja)
    public function scopeDadosDeBaja($query)
    {
        return $query->where('activo', false);
    }

    // 🔹 Darse de baja (sin eliminar)
    public function darDeBaja()
    {
        $this->update([
            'activo' => false,
            'fecha_baja' => now(),
            'fecha_eliminacion' => now()->addYear(),
        ]);
    }

    // 🔹 Reactivar (recuperar)
    public function reactivar()
    {
        $this->update([
            'activo' => true,
            'fecha_baja' => null,
            'fecha_eliminacion' => null,
        ]);
    }

    // 🔹 Scope: eliminar los que ya superaron su fecha programada
    public function scopeParaEliminacion($query)
    {
        return $query->where('activo', false)
            ->whereNotNull('fecha_eliminacion')
            ->where('fecha_eliminacion', '<=', Carbon::now());
    }

    // 🔹 Helper: saber si está vencido para borrar
    public function getDebeEliminarAttribute()
    {
        return !$this->activo &&
            $this->fecha_eliminacion &&
            $this->fecha_eliminacion->isPast();
    }
}
