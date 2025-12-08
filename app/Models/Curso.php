<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CursoEdicion;

class Curso extends Model
{
    use SoftDeletes;

    protected $table = 'cursos';
    protected $primaryKey = 'idcurso';

    protected $fillable = [
        'idprofesor',
        'idcategoria',
        'nombre',
        'slug',
        'descripcion',
        'nivel',
        'imagen',
        'estado',
        'promedio_resenas',
        'total_resenas',
        'duracion_total', // duración física total del curso
    ];

    protected $casts = [
        'duracion_total' => 'integer',
        'deleted_at'     => 'datetime',
    ];

    /* ======================================================
     * 🔗 RELACIONES PRINCIPALES
     * =====================================================*/

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'idprofesor', 'idprofesor');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'idcategoria', 'idcategoria');
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'idcurso', 'idcurso')
                    ->orderBy('idunidad');
    }

    public function clases()
    {
        return $this->hasManyThrough(
            Clase::class,
            Unidad::class,
            'idcurso',   // FK en unidades
            'idunidad',  // FK en clases
            'idcurso',   // PK en cursos
            'idunidad'   // PK en unidades
        );
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'idcurso', 'idcurso');
    }

    public function examenes()
    {
        return $this->hasMany(Examen::class, 'idcurso', 'idcurso');
    }

    public function juegos()
    {
        return $this->hasManyThrough(
            CursoJuego::class,
            Unidad::class,
            'idcurso',
            'idunidad',
            'idcurso',
            'idunidad'
        );
    }

    public function oferta()
    {
        return $this->hasOne(Oferta::class, 'idcurso', 'idcurso');
    }

    public function licencia()
    {
        return $this->hasOne(Licencia::class, 'idcurso', 'idcurso');
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class, 'idcurso', 'idcurso');
    }

    /* ======================================================
     * ✏️ RELACIONES PARA SOLICITUDES DE EDICIÓN
     * =====================================================*/

    public function ediciones()
    {
        return $this->hasMany(CursoEdicion::class, 'idcurso', 'idcurso');
    }

    /**
     * Relación hasOne: edición activa
     * Cuenta estados:
     *  - pendiente   (profe pidió edición)
     *  - en_edicion  (admin aprobó y profe puede editar)
     *  - en_revision (profe terminó cambios y los mandó a revisión)
     */
    public function edicionActiva()
    {
        return $this->hasOne(CursoEdicion::class, 'idcurso', 'idcurso')
            ->whereIn('estado', ['pendiente', 'en_edicion', 'en_revision', 'cerrada']) // 👈 agregado 'cerrada'
            ->latest();
    }

    public function tieneEdicionActiva(): bool
    {
        return $this->edicionActiva()->exists();
    }

    /* ======================================================
     * 🔁 RECÁLCULO DE DURACIÓN
     * =====================================================*/

    public function recalcularDuracion()
    {
        // Sumar duración física de todas las unidades
        $this->duracion_total = $this->unidades()->sum('duracion_total');
        $this->saveQuietly();
    }

    /* ======================================================
     * EVENTS → AUTO SYNC
     * =====================================================*/

    protected static function booted()
    {
        // Cuando este curso cambia, si cambia estado, lo propagamos
        static::updated(function (Curso $curso) {

            if ($curso->wasChanged('estado')) {

                $estado = $curso->estado;

                $idsUnidades = Unidad::where('idcurso', $curso->idcurso)->pluck('idunidad');

                if ($idsUnidades->isNotEmpty()) {

                    Unidad::whereIn('idunidad', $idsUnidades)->update(['estado' => $estado]);

                    $idsClases = Clase::whereIn('idunidad', $idsUnidades)->pluck('idclase');

                    if ($idsClases->isNotEmpty()) {
                        Clase::whereIn('idclase', $idsClases)->update(['estado' => $estado]);
                        Contenido::whereIn('idclase', $idsClases)->update(['estado' => $estado]);
                    }
                }
            }
        });
    }

    /* ======================================================
     * SCOPES (SIN WARNINGS)
     * =====================================================*/

    public function scopePublicado(Builder $query)
    {
        return $query->where('estado', 'publicado');
    }

    public function scopeActivos(Builder $query)
    {
        return $query->whereNull('deleted_at');
    }
}
