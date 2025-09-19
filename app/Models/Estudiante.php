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
    ];

    // 🔹 Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 🔹 Relación con matrículas
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'idestudiante', 'idestudiante');
    }

    // 🔹 Relación con juegos
    public function juegos()
    {
        return $this->hasMany(IntentoJuego::class, 'idestudiante', 'idestudiante');
    }

    // 🔹 Relación con exámenes
    public function examenes()
    {
        return $this->hasMany(IntentoExamen::class, 'idestudiante', 'idestudiante');
    }

    // 🔹 Relación muchos a muchos con categorías (intereses)
    public function categorias()
    {
        return $this->belongsToMany(
            Categoria::class,
            'estudiante_categoria',   // tabla pivot
            'idestudiante',           // FK local en pivot
            'idcategoria'             // FK relacionada
        )->withTimestamps();
    }
}
