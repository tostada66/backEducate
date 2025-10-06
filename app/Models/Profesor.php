<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'idprofesor';

    // ✅ Campos que se pueden asignar masivamente
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
        'estado_aprobacion' // 👈 nuevo campo
    ];

    // ✅ Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    public function perfil()
    {
        return $this->hasOne(PerfilUsuario::class, 'idusuario', 'idusuario');
    }

    // 🔹 Relación: Profesor → Cursos
    public function cursos()
    {
        return $this->hasMany(Curso::class, 'idprofesor', 'idprofesor');
    }

    // 🔹 Relación: Profesor → Ofertas (a través de sus cursos)
    public function ofertas()
    {
        return $this->hasManyThrough(
            Oferta::class,    // modelo final
            Curso::class,     // modelo intermedio
            'idprofesor',     // FK en cursos
            'idcurso',        // FK en ofertas
            'idprofesor',     // PK en profesor
            'idcurso'         // PK en curso
        );
    }

    // 🔹 Relación: Profesor → Licencias (a través de sus cursos)
    public function licencias()
    {
        return $this->hasManyThrough(
            Licencia::class,  // modelo final
            Curso::class,     // modelo intermedio
            'idprofesor',     // FK en cursos
            'idcurso',        // FK en licencias
            'idprofesor',     // PK en profesor
            'idcurso'         // PK en curso
        );
    }

    // 🔎 Scope: obtener solo profesores aprobados
    public function scopeAprobados($query)
    {
        return $query->where('estado_aprobacion', 'aprobado');
    }

    // 🔎 Scope: obtener pendientes
    public function scopePendientes($query)
    {
        return $query->where('estado_aprobacion', 'pendiente');
    }

    // ℹ️ Accessor: mostrar estado capitalizado
    public function getEstadoAprobacionFormattedAttribute()
    {
        return ucfirst($this->estado_aprobacion);
    }
}
