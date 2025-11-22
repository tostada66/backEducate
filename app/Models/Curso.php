<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

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
        'duracion_total', // ⚠️ NUEVO: duración física
    ];

    protected $casts = [
        'duracion_total' => 'integer',
        'deleted_at' => 'datetime',
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
            'idcurso',
            'idunidad',
            'idcurso',
            'idunidad'
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
     * 🔁 RECÁLCULO DE DURACIÓN
     * =====================================================*/

    public function recalcularDuracion()
    {
        // 1️⃣ Sumar duración física de todas las unidades
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

            /* ------------------------------
             *  🔁 PROPAGAR ESTADO HIJO → UNIT → CLASE → CONTENIDO
             * ------------------------------*/
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

        // Cuando se guarde o elimine una unidad ya lo recalcula Unidad->Curso
        // Así que Curso ya NO necesita más eventos aquí.
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
