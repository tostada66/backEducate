<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory;

    protected $table = 'ofertas';
    protected $primaryKey = 'idoferta';

    protected $fillable = [
        'idcurso',
        'idprofesor',
        'num_clases',
        'tarifa_por_clase',
        'tarifa_por_mes',
        'duracion_meses',
        'costo_total',
        'estado',
    ];

    // 🔗 Relaciones principales
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }

    // 🗒️ Nueva relación: observaciones (comentarios, rechazos, contraofertas)
    public function observaciones()
    {
        return $this->hasMany(Observacion::class, 'idoferta', 'idoferta');
    }
}
