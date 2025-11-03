<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoProfesor extends Model
{
    use HasFactory;

    protected $table = 'pagos_profesores';
    protected $primaryKey = 'idpago';

    protected $fillable = [
        'idprofesor',
        'idlicencia',
        'monto',
        'estado',
        'metodo_pago',
        'referencia',
        'fecha_generacion',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_generacion' => 'datetime',
        'fecha_pago' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELACIONES
    |--------------------------------------------------------------------------
    */

    // 📘 Un pago pertenece a un profesor
    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }

    // 🧾 Un pago pertenece a una licencia
    public function licencia()
    {
        return $this->belongsTo(Licencia::class, 'idlicencia', 'idlicencia');
    }

    // 🧮 Un pago puede tener una factura asociada (de tipo pago_profesor)
    public function factura()
    {
        return $this->hasOne(Factura::class, 'idpago_profesor', 'idpago')
            ->where('tipo', 'pago_profesor');
    }

    /*
    |--------------------------------------------------------------------------
    | 🔎 SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ MÉTODOS PERSONALIZADOS
    |--------------------------------------------------------------------------
    */

    // ✅ Marcar pago como completado y registrar datos opcionales
    public function marcarComoPagado($metodo = null, $referencia = null)
    {
        $this->update([
            'estado'       => 'pagado',
            'metodo_pago'  => $metodo,
            'referencia'   => $referencia,
            'fecha_pago'   => now(),
        ]);
    }

    // 🧾 Crear factura automática del pago
    public function generarFacturaAutomatica($idtipo_pago = null)
    {
        // Evita duplicar facturas o crear si no está pagado
        if ($this->estado !== 'pagado' || $this->factura) {
            return null;
        }

        return Factura::create([
            'idusuario'       => $this->profesor->usuario->idusuario,
            'tipo'            => 'pago_profesor',
            'idlicencia'      => $this->idlicencia,
            'idpago_profesor' => $this->idpago,         // ✅ correcto
            'idtipo_pago'     => $idtipo_pago,          // ✅ guarda FK real de tipos_pagos
            'total'           => $this->monto,
            'moneda'          => 'BOB',
            'estado'          => 'pagada',
            'referencia'      => $this->referencia,
            'nombre_factura'  => $this->profesor->usuario->nombres . ' ' . $this->profesor->usuario->apellidos,
            'razon_social'    => 'EduPlatform - Pago de Servicios',
        ]);
    }
}
