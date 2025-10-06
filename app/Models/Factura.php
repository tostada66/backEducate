<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'idfactura';

    protected $fillable = [
        'idusuario',
        'tipo',
        'idplan',
        'idlicencia',
        'idpago',
        'total',
        'moneda',
        'referencia',
        'nombre_factura', // 👈 nuevo
        'nit',
        'razon_social',
        'fecha',
        'estado',
        'pdf_path'
    ];

    protected $casts = [
        'fecha' => 'datetime', // 👈 esto hace que venga como Carbon en JSON
    ];

    // 🔗 Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    public function plan()
    {
        return $this->belongsTo(TipoPlan::class, 'idplan', 'idplan');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'idpago', 'idpago');
    }

    public function licencia()
    {
        return $this->belongsTo(Licencia::class, 'idlicencia', 'idlicencia');
    }

    public function suscripcion()
    {
        return $this->hasOne(Suscripcion::class, 'factura_id', 'idfactura');
    }
}
