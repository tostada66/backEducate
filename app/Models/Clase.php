<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clase extends Model
{
    use SoftDeletes;

    protected $table = 'clases';
    protected $primaryKey = 'idclase';

    protected $fillable = [
        'idunidad',
        'titulo',
        'descripcion',
        'orden',
        'estado',
    ];

    protected $dates = ['deleted_at'];

    // 👇 Se incluirá automáticamente en JSON
    protected $appends = ['duracion_total'];

    // 🔹 Relación: una clase tiene muchos contenidos
    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'idclase', 'idclase');
    }

    // 🔹 Relación: una clase pertenece a una unidad
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'idunidad', 'idunidad');
    }

    // 🔹 Accessor: duración total de la clase (suma de contenidos tipo video)
    public function getDuracionTotalAttribute()
    {
        return $this->contenidos()
            ->where('tipo', 'video')
            ->sum('duracion') ?? 0; // si no hay nada, devuelve 0
    }
}
