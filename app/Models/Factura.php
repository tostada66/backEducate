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
        'idtipo_pago',
        'idpago_profesor',
        'total',
        'moneda',
        'referencia',
        'nombre_factura',
        'nit',
        'razon_social',
        'fecha',
        'estado',
        'pdf_path',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELACIONES
    |--------------------------------------------------------------------------
    */

    // 👤 Usuario asociado (puede ser estudiante o profesor)
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 📦 Plan (solo para tipo = 'suscripcion')
    public function plan()
    {
        return $this->belongsTo(TipoPlan::class, 'idplan', 'idplan');
    }

    // 🏷️ Tipo de pago (efectivo, tarjeta, QR, etc.)
    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'idtipo_pago', 'idtipo_pago');
    }

    // 🎓 Licencia (para facturas de tipo 'licencia' o 'pago_profesor')
    public function licencia()
    {
        return $this->belongsTo(Licencia::class, 'idlicencia', 'idlicencia');
    }

    // 💸 Pago a profesor (solo si tipo = 'pago_profesor')
    public function pagoProfesor()
    {
        return $this->belongsTo(PagoProfesor::class, 'idpago_profesor', 'idpago');
    }

    // 💜 Cliente (alias de usuario para algunas vistas)
    public function cliente()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 🔗 Suscripción asociada (1 factura → 1 suscripción)
    public function suscripcion()
    {
        return $this->hasOne(Suscripcion::class, 'factura_id', 'idfactura');
    }
}
