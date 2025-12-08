<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'idnotificacion';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idusuario',
        'categoria',
        'tipo',
        'titulo',
        'mensaje',
        'url',
        'datos',
        'leido_en',
    ];

    protected $casts = [
        'datos'    => 'array',
        'leido_en' => 'datetime',
    ];

    // 🔗 Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idusuario', 'idusuario');
    }

    // 🔎 Scope para no leídas
    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leido_en');
    }

    /**
     * ✨ Helper estático clásico (el que ya tenías)
     */
    public static function crear(
        int $idusuario,
        string $categoria,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $url = null,
        array $datos = []
    ): self {
        return self::create([
            'idusuario' => $idusuario,
            'categoria' => $categoria,
            'tipo'      => $tipo,
            'titulo'    => $titulo,
            'mensaje'   => $mensaje,
            'url'       => $url,
            'datos'     => $datos,
        ]);
    }

    /**
     * ✨ Helper alternativo con array (para usar en controladores)
     * Ej:
     * Notificacion::crearParaUsuario($id, [
     *   'categoria' => 'cursos',
     *   'tipo'      => 'curso_aprobado',
     *   'titulo'    => 'Curso aprobado',
     *   'mensaje'   => 'Tu curso fue aprobado',
     *   'url'       => '/profesor/cursos/1',
     *   'datos'     => ['idcurso' => 1],
     * ]);
     */
    public static function crearParaUsuario(int $idusuario, array $data): self
    {
        return self::create([
            'idusuario' => $idusuario,
            'categoria' => $data['categoria'] ?? null,
            'tipo'      => $data['tipo'] ?? null,
            'titulo'    => $data['titulo'] ?? null,
            'mensaje'   => $data['mensaje'] ?? null,
            'url'       => $data['url'] ?? null,
            'datos'     => $data['datos'] ?? [],
        ]);
    }
}
