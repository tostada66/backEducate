<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuegoReciclajeItem extends Model
{
    use HasFactory;

    protected $table = 'juego_reciclaje_items';
    protected $primaryKey = 'iditem';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idcursojuego', // 🔗 Relación con curso_juego
        'nombre',       // Ej: 'Botella de plástico'
        'tipo',         // Ej: 'plástico', 'papel', 'vidrio', 'orgánico'
        'imagen',
        'activo',
        'nivel',        // (opcional, si agregas dificultad)
        'descripcion',  // (opcional, mensaje educativo)
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    // 🔹 Pertenece a un curso_juego
    public function cursoJuego()
    {
        return $this->belongsTo(CursoJuego::class, 'idcursojuego', 'idcursojuego');
    }

    /* ───────────────────────────────
     * 🔍 SCOPES
     * ─────────────────────────────── */

    // 🔹 Solo ítems activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // 🔹 Filtrar por tipo de residuo
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // 🔹 Filtrar por curso específico
    public function scopeDeCurso($query, $idcursojuego)
    {
        return $query->where('idcursojuego', $idcursojuego);
    }
}
