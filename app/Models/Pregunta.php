<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    use HasFactory;

    protected $table = 'preguntas';
    protected $primaryKey = 'idpregunta';

    protected $fillable = [
        'idexamen',
        'texto',              // contenido de la pregunta
        'tiempo_segundos',    // duración individual
        'puntos',             // puntos que vale
        'activa',             // si está activa o no
    ];

    // 🔗 Una pregunta pertenece a un examen
    public function examen()
    {
        return $this->belongsTo(Examen::class, 'idexamen', 'idexamen');
    }

    // 🧩 Una pregunta tiene varias posibles respuestas
    public function respuestas()
    {
        return $this->hasMany(Respuesta::class, 'idpregunta', 'idpregunta');
    }
}
