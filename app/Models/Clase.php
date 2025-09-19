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
        'idcurso',
        'titulo',
        'descripcion',
        'orden',
        'estado',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relación: una clase tiene muchos contenidos
     */
    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'idclase', 'idclase');
    }

    /**
     * Relación con curso
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }
}
