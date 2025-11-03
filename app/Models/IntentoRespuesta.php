<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoRespuesta extends Model
{
    use HasFactory;

    protected $table = 'intento_respuestas';
    protected $primaryKey = 'idintento_respuesta';

    protected $fillable = [
        'idintento',
        'idpregunta',
        'idrespuesta',
        'es_correcta',
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
    ];

    // 🔗 Relaciones
    public function intento()
    {
        return $this->belongsTo(IntentoExamen::class, 'idintento', 'idintento');
    }

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class, 'idpregunta', 'idpregunta');
    }

    public function respuesta()
    {
        return $this->belongsTo(Respuesta::class, 'idrespuesta', 'idrespuesta');
    }
}
