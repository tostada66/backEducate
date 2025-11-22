<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    protected $table = 'comentarios';
    protected $primaryKey = 'idcomentario';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idclase',
        'idusuario',
        'idpadre',
        'contenido',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
    ];

    protected $appends = ['autor_nombre', 'foto_url'];

    /* ───────────────────────────────
     * 👤 Relación: comentario → usuario
     * ─────────────────────────────── */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    /* ───────────────────────────────
     * 📚 Relación: comentario → clase
     * ─────────────────────────────── */
    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idclase', 'idclase');
    }

    /* ───────────────────────────────
     * 💬 Relación: comentario → respuestas
     * ─────────────────────────────── */
    public function respuestas()
    {
        return $this->hasMany(Comentario::class, 'idpadre', 'idcomentario')
                    ->with(['usuario']);
    }

    /* ───────────────────────────────
     * 🔗 Relación: comentario → padre
     * ─────────────────────────────── */
    public function padre()
    {
        return $this->belongsTo(Comentario::class, 'idpadre', 'idcomentario');
    }

    /* ───────────────────────────────
     * 🧠 Accessor: autor_nombre
     * ─────────────────────────────── */
    public function getAutorNombreAttribute()
    {
        if (!$this->relationLoaded('usuario')) {
            $this->load('usuario');
        }

        return $this->usuario
            ? trim("{$this->usuario->nombres} {$this->usuario->apellidos}")
            : 'Usuario eliminado';
    }

    /* ───────────────────────────────
     * 🖼️ Accessor: foto_url
     * ─────────────────────────────── */
    public function getFotoUrlAttribute()
    {
        if (!$this->relationLoaded('usuario')) {
            $this->load('usuario');
        }

        if ($this->usuario && $this->usuario->foto) {
            return asset('storage/' . $this->usuario->foto);
        }

        return 'https://cdn.quasar.dev/img/avatar.png';
    }
}
