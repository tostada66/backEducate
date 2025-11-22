<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model
{
    use HasFactory;

    protected $table = 'respuestas';
    protected $primaryKey = 'idrespuesta';

    protected $fillable = [
        'idpregunta',
        'texto',
        'es_correcta',
        'activa',
    ];

    // 🔗 Cada respuesta pertenece a una pregunta
    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class, 'idpregunta', 'idpregunta');
    }
}
