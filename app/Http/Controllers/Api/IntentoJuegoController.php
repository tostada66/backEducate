<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntentoJuego;
use App\Models\CursoJuego;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntentoJuegoController extends Controller
{
    /**
     * 🧾 Listar intentos del estudiante autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró un perfil de estudiante asociado al usuario.'
            ], 404);
        }

        $intentos = IntentoJuego::with([
            // Aseguramos juego y relación unidad->curso para que venga completo
            'cursoJuego.juego:idjuego,nombre',
            'cursoJuego.unidad.curso:idcurso,nombre',
        ])
            ->where('idestudiante', $user->estudiante->idestudiante)
            ->orderByDesc('fecha')
            ->get();

        return response()->json([
            'ok'    => true,
            'total' => $intentos->count(),
            'data'  => $intentos
        ]);
    }

    /**
     * 🧠 Registrar un nuevo intento de juego
     */
    public function store(Request $request, $idcursojuego)
    {
        $user = $request->user();

        // 🔍 Verificar que el curso_juego exista
        $cursoJuego = CursoJuego::with('juego')->find($idcursojuego);
        if (!$cursoJuego) {
            return response()->json([
                'ok' => false,
                'message' => 'Instancia de curso_juego no encontrada.'
            ], 404);
        }

        if (!$user->estudiante) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario no tiene perfil de estudiante asociado.'
            ], 400);
        }

        // ✅ Validar datos del intento
        $validated = $request->validate([
            'puntaje'        => 'required|numeric|min:0',
            'aciertos'       => 'nullable|integer|min:0',
            'errores'        => 'nullable|integer|min:0',
            'tiempo'         => 'nullable|integer|min:0',
            'nivel_superado' => 'nullable|integer|min:1',
            'detalles'       => 'nullable|array',
        ]);

        try {
            // 📝 Crear intento
            $intento = IntentoJuego::create([
                'idestudiante'   => $user->estudiante->idestudiante,
                'idcursojuego'   => $idcursojuego,
                'puntaje'        => $validated['puntaje'],
                'aciertos'       => $validated['aciertos'] ?? 0,
                'errores'        => $validated['errores'] ?? 0,
                'tiempo'         => $validated['tiempo'] ?? 0,
                'nivel_superado' => $validated['nivel_superado'] ?? null,
                'detalles'       => $validated['detalles'] ?? [],
                'fecha'          => now(),
            ]);

            // Cargar relaciones mínimas
            $intento->load([
                'cursoJuego.juego:idjuego,nombre',
                'cursoJuego.unidad.curso:idcurso,nombre',
            ]);

            // Añadir idcurso e idunidad al payload (para el frontend)
            $intento->idcurso  = optional(optional($intento->cursoJuego->unidad)->curso)->idcurso;
            $intento->idunidad = optional($intento->cursoJuego->unidad)->idunidad;

            return response()->json([
                'ok'      => true,
                'message' => 'Intento registrado correctamente.',
                'data'    => $intento
            ], 201);

        } catch (\Throwable $th) {
            Log::error('Error al registrar intento: '.$th->getMessage());
            return response()->json([
                'ok'      => false,
                'message' => 'Ocurrió un error al registrar el intento.',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Mostrar detalle de un intento específico
     * GET /api/juegos/intentos/{idintento}
     */
    public function show($idintento)
    {
        $intento = IntentoJuego::with([
            'cursoJuego.juego:idjuego,nombre',
            'cursoJuego.unidad.curso:idcurso,nombre',
            'estudiante.usuario:idusuario,nombres,apellidos,foto'
        ])->find($idintento);

        if (!$intento) {
            return response()->json([
                'ok' => false,
                'message' => 'Intento no encontrado.'
            ], 404);
        }

        // 👉 Agregamos estos dos campos que espera el frontend
        $intento->idcurso  = optional(optional($intento->cursoJuego->unidad)->curso)->idcurso;
        $intento->idunidad = optional($intento->cursoJuego->unidad)->idunidad;

        return response()->json([
            'ok'   => true,
            'data' => $intento
        ]);
    }

    /**
     * 📈 Ranking general (por curso_juego)
     */
    public function ranking($idcursojuego)
    {
        $cursoJuego = CursoJuego::with('juego')->find($idcursojuego);

        if (!$cursoJuego) {
            return response()->json([
                'ok' => false,
                'message' => 'Instancia de curso_juego no encontrada.'
            ], 404);
        }

        $ranking = IntentoJuego::with('estudiante.usuario:idusuario,nombres,apellidos,foto')
            ->where('idcursojuego', $idcursojuego)
            ->orderByDesc('puntaje')
            ->take(10)
            ->get(['idintento', 'idestudiante', 'puntaje', 'aciertos', 'errores', 'tiempo']);

        return response()->json([
            'ok'   => true,
            'data' => [
                'juego' => [
                    'idjuego' => $cursoJuego->juego->idjuego ?? null,
                    'nombre'  => $cursoJuego->juego->nombre ?? 'Desconocido',
                ],
                'ranking' => $ranking
            ]
        ]);
    }
}
