<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Contenido extends Model
{
    use SoftDeletes;

    protected $table = 'contenidos';
    protected $primaryKey = 'idcontenido';

    protected $fillable = [
        'idclase',
        'titulo',
        'descripcion',
        'tipo',
        'url',
        'orden',
        'estado'
    ];

    // 👉 Accessor para incluir URL pública automáticamente
    protected $appends = ['url_publica'];

    public function getUrlPublicaAttribute()
    {
        if ($this->url && !filter_var($this->url, FILTER_VALIDATE_URL)) {
            return Storage::url($this->url);
        }
        return $this->url;
    }

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }
}
