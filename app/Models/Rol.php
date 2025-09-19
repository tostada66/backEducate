<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'idrol';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['nombre','descripcion'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'idrol', 'idrol');
    }
}
