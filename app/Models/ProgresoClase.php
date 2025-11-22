<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresoClase extends Model
{
    protected $table = 'progreso_clases';
    protected $primaryKey = 'idprogreso';
    public $timestamps = true;

    protected $fillable = [
        'idmatricula',
        'idclase',
        'completado',
        'progreso',
        'ultima_vista_at',
    ];

    // 🔹 Relaciones
    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'idmatricula', 'idmatricula');
    }

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }
}
