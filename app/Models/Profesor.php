<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'idprofesor';
    protected $fillable = ['idusuario','bio','especialidad'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    public function perfil()
    {
        return $this->hasOne(PerfilUsuario::class, 'idusuario', 'idusuario');
    }
}
