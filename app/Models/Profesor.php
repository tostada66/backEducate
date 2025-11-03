<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'idprofesor';

    protected $fillable = [
        'idusuario',
        'bio',
        'especialidad',
        'direccion',
        'pais',
        'empresa',
        'cargo',
        'fecha_inicio',
        'fecha_fin',
        'detalles',
        'estado_aprobacion'
    ];

    // 🔗 Relación principal con usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 🔗 Perfil extendido
    public function perfil()
    {
        return $this->hasOne(PerfilUsuario::class, 'idusuario', 'idusuario');
    }

    // 🔹 Cursos del profesor
    public function cursos()
    {
        return $this->hasMany(Curso::class, 'idprofesor', 'idprofesor');
    }

    // 🔹 Ofertas del profesor (a través de cursos)
    public function ofertas()
    {
        return $this->hasManyThrough(
            Oferta::class,
            Curso::class,
            'idprofesor',
            'idcurso',
            'idprofesor',
            'idcurso'
        );
    }

    // 🔹 Licencias del profesor (a través de cursos)
    public function licencias()
    {
        return $this->hasManyThrough(
            Licencia::class,
            Curso::class,
            'idprofesor',
            'idcurso',
            'idprofesor',
            'idcurso'
        );
    }

    // 💰 Pagos recibidos por el profesor
    public function pagos()
    {
        return $this->hasMany(PagoProfesor::class, 'idprofesor', 'idprofesor');
    }

    // 🧾 Facturas relacionadas a esos pagos (opcional pero útil)
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'idusuario', 'idusuario')
            ->where('tipo', 'pago_profesor');
    }

    // 🔎 Scopes útiles
    public function scopeAprobados($query)
    {
        return $query->where('estado_aprobacion', 'aprobado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_aprobacion', 'pendiente');
    }

    // 🧠 Accessor: estado capitalizado
    public function getEstadoAprobacionFormattedAttribute()
    {
        return ucfirst($this->estado_aprobacion);
    }
}
