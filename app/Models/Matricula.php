<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $table = 'matriculas';
    protected $primaryKey = 'idmatricula';
    public $timestamps = true;

    protected $fillable = [
        'idestudiante',
        'idcurso',
        'fecha',
        'estado',
    ];

    // 🔹 Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function progresoClases()
    {
        return $this->hasMany(ProgresoClase::class, 'idmatricula', 'idmatricula');
    }

    // 🔹 Accesor para calcular % de avance
    public function getPorcentajeAvanceAttribute()
    {
        $total = $this->curso->unidades->flatMap->clases->count();
        $completadas = $this->progresoClases()->where('completado', true)->count();

        return $total > 0 ? round(($completadas / $total) * 100, 2) : 0;
    }
}
