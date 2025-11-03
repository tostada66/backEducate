<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens;
    use Notifiable;
    use CanResetPassword;

    protected $table = 'usuarios';
    protected $primaryKey = 'idusuario';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idrol',
        'nombres',
        'apellidos',
        'correo',
        'nombreusuario',
        'telefono',
        'password',
        'estado',
        'foto',
        'oauth_provider',
        'oauth_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // 📸 Devuelve la URL de la foto del usuario
    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    // 🔐 Configuración para reseteo de contraseña
    public function getEmailForPasswordReset()
    {
        return $this->correo;
    }

    public function routeNotificationForMail()
    {
        return $this->correo;
    }

    // 👤 Relaciones principales
    public function rolRel()
    {
        return $this->belongsTo(Rol::class, 'idrol', 'idrol');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'idusuario', 'idusuario');
    }

    public function profesor()
    {
        return $this->hasOne(Profesor::class, 'idusuario', 'idusuario');
    }

    public function perfil()
    {
        return $this->hasOne(PerfilUsuario::class, 'idusuario', 'idusuario');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'idusuario', 'idusuario');
    }

    // 🗒️ Nueva relación: observaciones (comentarios de revisión, rechazos, etc.)
    public function observaciones()
    {
        return $this->hasMany(Observacion::class, 'idusuario', 'idusuario');
    }

    // 💬 Nueva relación: comentarios en clases (tipo YouTube)
    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'idusuario', 'idusuario');
    }
}
