<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CursoEdicion extends Model
{
    use HasFactory;

    // 🔗 Nombre de la tabla
    protected $table = 'curso_ediciones';

    // ✅ Campos asignables en masa
    protected $fillable = [
        'idcurso',
        'idprofesor',
        'motivo',
        'estado',
        'aprobado_at',
        'cerrado_at',
    ];

    // ⏱️ Casts para fechas
    protected $casts = [
        'aprobado_at' => 'datetime',
        'cerrado_at'  => 'datetime',
    ];

    /**
     * 🔗 Relación: esta edición pertenece a un curso
     */
    public function curso()
    {
        // PK de cursos = idcurso (no es el id por defecto)
        return $this->belongsTo(Curso::class, 'idcurso', 'idcurso');
    }

    /**
     * 🔗 Relación: esta edición pertenece a un profesor
     */
    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }
}
