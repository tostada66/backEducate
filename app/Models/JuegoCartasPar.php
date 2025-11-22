<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuegoCartasPar extends Model
{
    use HasFactory;

    // 🧩 Nombre de la tabla
    protected $table = 'juego_cartas_pares';

    // 🆔 Clave primaria
    protected $primaryKey = 'idpar';
    public $incrementing = true;
    protected $keyType = 'int';

    // 📝 Campos que se pueden asignar masivamente
    protected $fillable = [
        'idcursojuego',
        'texto_a',
        'imagen_a',
        'texto_b',
        'imagen_b',
        'activo',
    ];

    // 🧠 Casting de tipos
    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    /**
     * 🎮 Un par de cartas pertenece a un curso_juego (instancia dentro de una unidad).
     */
    public function cursoJuego()
    {
        return $this->belongsTo(CursoJuego::class, 'idcursojuego', 'idcursojuego');
    }

    /**
     * 💡 Acceso rápido a la unidad y curso a través del curso_juego.
     */
    public function unidad()
    {
        return $this->hasOneThrough(
            Unidad::class,
            CursoJuego::class,
            'idcursojuego', // FK en curso_juego
            'idunidad',     // FK en unidades
            'idcursojuego', // local key en esta tabla
            'idunidad'      // local key en curso_juego
        );
    }

    /* ───────────────────────────────
     * 🔍 SCOPES (consultas personalizadas)
     * ─────────────────────────────── */

    /**
     * Scope: Solo pares activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Obtener un número aleatorio de pares.
     */
    public function scopeAleatorios($query, $cantidad = 8)
    {
        return $query->inRandomOrder()->take($cantidad);
    }
}
