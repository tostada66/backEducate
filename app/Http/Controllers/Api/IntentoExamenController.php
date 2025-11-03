<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\IntentoExamen;
use App\Models\IntentoRespuesta;
use App\Models\Respuesta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IntentoExamenController extends Controller
{
    /**
     * 🆕 Crear nuevo intento de examen
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->estudiante) {
            return response()->json(['message' => 'Solo los estudiantes pueden iniciar intentos.'], 403);
        }

        $data = $request->validate([
            'idexamen' => 'required|exists:examenes,idexamen',
        ]);

        $examen = Examen::findOrFail($data['idexamen']);

        $intento = IntentoExamen::create([
            'idexamen' => $examen->idexamen,
            'idestudiante' => $user->estudiante->idestudiante,
            'vidas_restantes' => $examen->vidas,
            'fecha_inicio' => Carbon::now(),
            'estado' => 'en_progreso',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Intento iniciado correctamente',
            'intento' => $intento,
        ]);
    }

    /**
     * 💾 Registrar respuesta seleccionada por el estudiante
     */
    public function responder(Request $request, $idintento)
    {
        $data = $request->validate([
            'idpregunta' => 'required|exists:preguntas,idpregunta',
            'idrespuesta' => 'nullable|exists:respuestas,idrespuesta',
        ]);

        $intento = IntentoExamen::findOrFail($idintento);
        $respuesta = $data['idrespuesta'] ? Respuesta::find($data['idrespuesta']) : null;
        $esCorrecta = $respuesta?->es_correcta ?? false;

        // Guardar la respuesta
        IntentoRespuesta::create([
            'idintento' => $intento->idintento,
            'idpregunta' => $data['idpregunta'],
            'idrespuesta' => $data['idrespuesta'],
            'es_correcta' => $esCorrecta,
        ]);

        // Restar vida si es incorrecta
        if (!$esCorrecta) {
            $intento->vidas_restantes = max(0, $intento->vidas_restantes - 1);
            $intento->save();
        }

        return response()->json([
            'ok' => true,
            'correcta' => $esCorrecta,
            'vidas_restantes' => $intento->vidas_restantes,
        ]);
    }

    /**
     * ✅ Finalizar intento (calcular puntaje y estado final)
     */
    public function finalizar($idintento)
    {
        $intento = IntentoExamen::with(['examen', 'respuestas'])->findOrFail($idintento);

        $totalRespuestas = $intento->respuestas->count();
        $correctas = $intento->respuestas->where('es_correcta', true)->count();
        $puntaje = $totalRespuestas > 0 ? round(($correctas / $totalRespuestas) * 100) : 0;
        $aprobado = $puntaje >= $intento->examen->minimo_aprobacion;

        $intento->update([
            'puntaje' => $puntaje,
            'aprobado' => $aprobado,
            'estado' => 'completado',
            'fecha_fin' => Carbon::now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Intento finalizado correctamente',
            'puntaje' => $puntaje,
            'aprobado' => $aprobado,
            'intento' => $intento,
        ]);
    }

    /**
     * 📄 Obtener resultado completo de un intento (con correctas, incorrectas, vacías)
     */
    public function show($idintento)
    {
        $intento = IntentoExamen::with([
            'examen.unidad.curso',
            'respuestas.pregunta',
            'respuestas.respuesta'
        ])->findOrFail($idintento);

        // 🕒 Calcular tiempo usado
        $tiempoUsado = 0;
        if ($intento->fecha_inicio && $intento->fecha_fin) {
            $tiempoUsado = Carbon::parse($intento->fecha_inicio)
                ->diffInSeconds(Carbon::parse($intento->fecha_fin));
        }

        // 🧮 Calcular correctas / incorrectas / no respondidas (igual que en estadísticas)
        $totalPreguntas = $intento->examen->preguntas()->count();

        $correctas = $intento->respuestas->where('es_correcta', true)->count();

        // Incorrectas con respuesta seleccionada
        $incorrectas = $intento->respuestas
            ->where('es_correcta', false)
            ->whereNotNull('idrespuesta')
            ->count();

        // No respondidas explícitamente (idrespuesta null)
        $noRespondidas = $intento->respuestas
            ->whereNull('idrespuesta')
            ->count();

        // Si faltan registros (por error o interrupción)
        $totalRegistradas = $correctas + $incorrectas + $noRespondidas;
        if ($totalRegistradas < $totalPreguntas) {
            $noRespondidas += ($totalPreguntas - $totalRegistradas);
        }

        // 🧭 Imagen unidad
        if ($intento->examen && $intento->examen->unidad) {
            $unidad = $intento->examen->unidad;
            $unidad->imagen_url = $unidad->imagen
                ? asset('storage/' . ltrim($this->cleanPath($unidad->imagen), '/'))
                : asset('storage/default_image.png');
        }

        return response()->json([
            'idintento' => $intento->idintento,
            'examen' => $intento->examen,
            'puntaje' => $intento->puntaje,
            'vidas_restantes' => $intento->vidas_restantes,
            'aprobado' => $intento->aprobado,
            'tiempo_usado' => $tiempoUsado,
            'correctas' => $correctas,
            'incorrectas' => $incorrectas,
            'vacias' => $noRespondidas,
        ]);
    }

    /**
     * 📊 Obtener estadísticas generales del examen
     */
    public function estadisticas($idexamen)
    {
        $examen = Examen::with(['unidad', 'preguntas'])->findOrFail($idexamen);

        $intentos = IntentoExamen::with(['estudiante.usuario', 'respuestas'])
            ->where('idexamen', $idexamen)
            ->where('estado', 'completado')
            ->get();

        if ($intentos->isEmpty()) {
            return response()->json([
                'examen' => $examen,
                'promedio' => 0,
                'aprobadosPorcentaje' => 0,
                'intentos' => [],
            ]);
        }

        $promedio = round($intentos->avg('puntaje'));
        $totalAprobados = $intentos->where('aprobado', true)->count();
        $aprobadosPorcentaje = round(($totalAprobados / $intentos->count()) * 100);
        $totalPreguntas = $examen->preguntas->count();

        $detalle = $intentos->map(function ($i) use ($totalPreguntas) {
            $usuario = $i->estudiante?->usuario;
            $nombreCompleto = $usuario
                ? trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''))
                : 'Sin nombre';

            $correctas = $i->respuestas->where('es_correcta', true)->count();

            // Incorrectas con respuesta seleccionada
            $incorrectas = $i->respuestas
                ->where('es_correcta', false)
                ->whereNotNull('idrespuesta')
                ->count();

            // No respondidas (sin respuesta)
            $noRespondidas = $i->respuestas
                ->whereNull('idrespuesta')
                ->count();

            $totalRegistradas = $correctas + $incorrectas + $noRespondidas;
            if ($totalRegistradas < $totalPreguntas) {
                $noRespondidas += ($totalPreguntas - $totalRegistradas);
            }

            return [
                'idintento' => $i->idintento,
                'estudiante' => $nombreCompleto,
                'puntaje' => $i->puntaje,
                'correctas' => $correctas,
                'incorrectas' => $incorrectas,
                'noRespondidas' => $noRespondidas,
                'tiempo' => $this->formatearTiempo($i->fecha_inicio, $i->fecha_fin),
                'vidas_restantes' => $i->vidas_restantes,
                'aprobado' => $i->aprobado,
            ];
        });

        return response()->json([
                   'examen' => $examen,
                   'promedio' => $promedio,
                   'aprobadosPorcentaje' => $aprobadosPorcentaje,
                   'intentos' => $detalle,
               ]);
    }

    /**
     * 🕒 Formatear tiempo
     */
    private function formatearTiempo($inicio, $fin)
    {
        if (!$inicio || !$fin) {
            return '—';
        }
        $segundos = Carbon::parse($inicio)->diffInSeconds(Carbon::parse($fin));
        $min = floor($segundos / 60);
        $seg = $segundos % 60;

        return "{$min}m {$seg}s";
    }

    /**
     * 🧹 Limpiar ruta para asset()
     */
    private function cleanPath($path)
    {
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }
}
