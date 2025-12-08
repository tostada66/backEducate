<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';
    protected $primaryKey = 'idestudiante';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idusuario',
        'nivelacademico',
        'escuela',
        'bio',
    ];

    /* ───────────────────────────────
     * 🔗 RELACIONES
     * ─────────────────────────────── */

    // 👤 Usuario asociado
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 🧾 Matrículas
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'idestudiante', 'idestudiante');
    }

    // 🎮 Intentos de juegos realizados
    public function intentosJuego()
    {
        return $this->hasMany(IntentoJuego::class, 'idestudiante', 'idestudiante');
    }

    // 🧪 Exámenes realizados
    public function examenes()
    {
        return $this->hasMany(IntentoExamen::class, 'idestudiante', 'idestudiante');
    }

    // 🏷️ Categorías de interés
    public function categorias()
    {
        return $this->belongsToMany(
            Categoria::class,
            'estudiante_categoria',   // tabla pivot
            'idestudiante',           // FK local
            'idcategoria'             // FK relacionada
        )->withTimestamps();
    }

    // ⭐ Reseñas de cursos
    public function resenas()
    {
        return $this->hasMany(Resena::class, 'idestudiante', 'idestudiante');
    }
}
