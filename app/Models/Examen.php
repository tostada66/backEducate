<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use HasFactory;

    protected $table = 'examenes';
    protected $primaryKey = 'idexamen';

    protected $fillable = [
        'idunidad',
        'titulo',
        'descripcion',
        'duracion_segundos',
        'vidas',
        'minimo_aprobacion',
        'activo',
    ];

    // 🔗 Cada examen pertenece a una unidad
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    // 📋 Un examen tiene muchas preguntas
    public function preguntas()
    {
        return $this->hasMany(Pregunta::class, 'idexamen', 'idexamen');
    }

    // 🧩 Un examen tiene varios intentos de estudiantes
    public function intentos()
    {
        return $this->hasMany(IntentoExamen::class, 'idexamen', 'idexamen');
    }
}
