<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPlan extends Model
{
    protected $table = 'tipo_planes';
    protected $primaryKey = 'idplan';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion', // 🔹 duración en meses
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'idplan', 'idplan');
    }
}
