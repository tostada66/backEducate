<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    use HasFactory;

    protected $table = 'observaciones';
    protected $primaryKey = 'idobservacion';
    public $timestamps = true;

    protected $fillable = [
        'idcurso',
        'idoferta',
        'idusuario',
        'tipo',
        'comentario',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Accessor para devolver la fecha formateada (opcional)
    protected $appends = ['created_at_formatted'];

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('d/m/Y H:i')
            : null;
    }

    // 🔗 Relaciones
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'idoferta', 'idoferta');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }
}
