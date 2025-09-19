<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuarios';
    protected $primaryKey = 'idperfil';
    protected $fillable = ['idusuario','linkedin_url','github_url','web_url','bio'];

    public function usuario(){ return $this->belongsTo(Usuario::class,'idusuario','idusuario'); }
}
