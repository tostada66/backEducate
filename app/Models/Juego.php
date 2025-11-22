<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Juego extends Model
{
    protected $table = 'juegos';
    protected $primaryKey = 'idjuego';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    // 🎮 Juegos asignados a unidades (instancias)
    public function cursoJuegos()
    {
        return $this->hasMany(CursoJuego::class, 'idjuego', 'idjuego');
    }

    // 📘 Unidades donde se usa este juego (por medio de curso_juego)
    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'curso_juego', 'idjuego', 'idunidad')
                    ->withPivot('idcursojuego', 'nombre_tema', 'nivel', 'activo')
                    ->withTimestamps();
    }

    // 💡 Si necesitas acceder indirectamente a los cursos:
    public function cursos()
    {
        return $this->hasManyThrough(
            Curso::class,
            Unidad::class,
            'idcurso',   // FK en unidades
            'idcurso',   // FK en cursos
            'idjuego',   // Local key (no se usa en la práctica, pero mantiene compatibilidad)
            'idcurso'    // Local key en unidades
        );
    }
}
