<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    use HasFactory;

    protected $table = 'licencias';
    protected $primaryKey = 'idlicencia';

    // Campos que puedes asignar masivamente
    protected $fillable = [
        'idcurso',
        'idprofesor',
        'num_clases',
        'tarifa_por_clase',
        'duracion_meses',
        'costo',
        'fechainicio',
        'fechafin',
        'estado',
    ];

    // 🔗 Relaciones
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }
}
