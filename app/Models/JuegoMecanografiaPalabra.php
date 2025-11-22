<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuegoMecanografiaPalabra extends Model
{
    use HasFactory;

    protected $table = 'juego_mecanografia_palabras';
    protected $primaryKey = 'idpalabra';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idcursojuego',   // 🔗 relación con la instancia del juego dentro de una unidad
        'palabra',        // texto o frase a escribir
        'tiempo',         // tiempo límite o recomendado en segundos
        'dificultad',     // fácil, medio o difícil
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tiempo' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    // 🎮 Instancia del juego dentro de la unidad
    public function cursoJuego()
    {
        return $this->belongsTo(CursoJuego::class, 'idcursojuego', 'idcursojuego');
    }

    // 💡 Acceso rápido al juego base (por si se necesita saber el tipo de juego)
    public function juego()
    {
        return $this->hasOneThrough(
            Juego::class,
            CursoJuego::class,
            'idcursojuego',  // FK en curso_juego
            'idjuego',       // FK en juegos
            'idcursojuego',  // local en esta tabla
            'idjuego'        // local en curso_juego
        );
    }

    /* ───────────────────────────────
     * 🎯 SCOPES ÚTILES
     * ─────────────────────────────── */

    // 🔹 Solo palabras activas
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // 🔹 Filtrar por dificultad
    public function scopePorDificultad($query, $nivel)
    {
        return $query->where('dificultad', $nivel);
    }
}
