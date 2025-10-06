<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';
    protected $primaryKey = 'idsus';

    protected $fillable = [
        'idestudiante',
        'idplan',
        'factura_id',   // 👈 añadimos este campo
        'fecha_inicio',
        'fecha_fin',
        'estado'
    ];

    protected $casts = [
        'estado'       => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date'
    ];

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'idestudiante', 'idestudiante');
    }

    public function plan()
    {
        return $this->belongsTo(TipoPlan::class, 'idplan', 'idplan');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id', 'idfactura'); // 👈 relación nueva
    }
}
