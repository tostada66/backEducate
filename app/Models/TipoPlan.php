<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPlan extends Model
{
    protected $table = 'tipo_planes';
    protected $primaryKey = 'idplan';
    protected $fillable = ['nombre','descripcion','precio'];

    public function facturas(){ return $this->hasMany(Factura::class,'idplan','idplan'); }
}
