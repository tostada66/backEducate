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
        'detalles'
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
}
