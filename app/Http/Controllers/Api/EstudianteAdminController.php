<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Support\Facades\DB;

class EstudianteAdminController extends Controller
{
    /**
     * 📋 Listar todos los estudiantes (con cursos activos si existen)
     */
    public function index()
    {
        $estudiantes = Estudiante::select(
                'estudiantes.idestudiante',
                'usuarios.idusuario',
                'usuarios.nombres',
                'usuarios.apellidos',
                'usuarios.correo',
                'usuarios.estado',
                'usuarios.created_at',
                // 🔹 Mostrar todos los cursos activos separados por coma
                DB::raw('(
                    SELECT GROUP_CONCAT(c.nombre SEPARATOR ", ")
                    FROM matriculas m
                    JOIN cursos c ON c.idcurso = m.idcurso
                    WHERE m.idestudiante = estudiantes.idestudiante
                      AND m.estado = "activa"
                ) as curso_nombre'),
                DB::raw('(SELECT COUNT(*) FROM matriculas m
                          WHERE m.idestudiante = estudiantes.idestudiante
                            AND m.estado = "activa") as total_cursos')
            )
            ->leftJoin('usuarios', 'usuarios.idusuario', '=', 'estudiantes.idusuario')
            ->orderByDesc('usuarios.created_at')
            ->get();

        return response()->json($estudiantes);
    }
}
