<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    /**
     * 📋 Listar exámenes de una unidad
     */
    public function index($idunidad)
    {
        $examenes = Examen::where('idunidad', $idunidad)
            ->withCount('preguntas')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($examenes);
    }

    /**
     * 📄 Obtener un examen específico con su unidad, curso e imagen mapeada
     */
    public function show($idexamen)
    {
        $examen = Examen::with([
            'unidad.curso',
            'preguntas.respuestas'
        ])->findOrFail($idexamen);

        // 🧭 Mapear URL de la unidad (igual que en UnidadController)
        if ($examen->unidad) {
            $examen->unidad->imagen_url = $examen->unidad->imagen
                ? asset('storage/' . ltrim($this->cleanPath($examen->unidad->imagen), '/'))
                : asset('storage/default_image.png');
        }

        return response()->json($examen);
    }

    /**
     * 🔍 Obtener examen por unidad (con curso, preguntas y URLs)
     */
    public function getByUnidad($idunidad)
    {
        $examen = Examen::where('idunidad', $idunidad)
            ->with(['unidad.curso', 'preguntas.respuestas'])
            ->first();

        if (!$examen) {
            return response()->json(null, 200);
        }

        if ($examen->unidad) {
            $examen->unidad->imagen_url = $examen->unidad->imagen
                ? asset('storage/' . ltrim($this->cleanPath($examen->unidad->imagen), '/'))
                : asset('storage/default_image.png');
        }

        return response()->json($examen, 200);
    }

    /**
     * 🆕 Crear examen con preguntas y respuestas
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'idunidad' => 'required|exists:unidades,idunidad',
            'titulo' => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'vidas' => 'required|integer|min:1',
            'minimo_aprobacion' => 'required|integer|min:0|max:100',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tiempo_segundos' => 'required|integer|min:5',
            'preguntas.*.puntos' => 'required|integer|min:1',
            'preguntas.*.respuestas' => 'required|array|min:2',
            'preguntas.*.respuesta_correcta' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            // 🧩 Crear examen
            $examen = Examen::create([
                'idunidad' => $data['idunidad'],
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'vidas' => $data['vidas'],
                'minimo_aprobacion' => $data['minimo_aprobacion'],
                'activo' => true,
                'duracion_segundos' => 0,
            ]);

            $total_duracion = 0;

            // 🧠 Guardar preguntas y respuestas
            foreach ($data['preguntas'] as $preg) {
                $pregunta = Pregunta::create([
                    'idexamen' => $examen->idexamen,
                    'texto' => $preg['texto'],
                    'tiempo_segundos' => $preg['tiempo_segundos'],
                    'puntos' => $preg['puntos'],
                ]);

                $total_duracion += $preg['tiempo_segundos'];

                foreach ($preg['respuestas'] as $ri => $respuesta) {
                    Respuesta::create([
                        'idpregunta' => $pregunta->idpregunta,
                        'texto' => $respuesta['texto'],
                        'es_correcta' => $ri === $preg['respuesta_correcta'],
                    ]);
                }
            }

            // ⏱ Actualizar duración total
            $examen->update(['duracion_segundos' => $total_duracion]);

            DB::commit();

            $examen->load(['preguntas.respuestas', 'unidad.curso']);
            if ($examen->unidad) {
                $examen->unidad->imagen_url = $examen->unidad->imagen
                    ? asset('storage/' . ltrim($this->cleanPath($examen->unidad->imagen), '/'))
                    : asset('storage/default_image.png');
            }

            return response()->json([
                'ok' => true,
                'message' => 'Examen creado correctamente',
                'examen' => $examen,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al crear el examen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔄 Actualizar examen existente
     */
    public function update(Request $request, $idexamen)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'vidas' => 'required|integer|min:1',
            'minimo_aprobacion' => 'required|integer|min:0|max:100',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tiempo_segundos' => 'required|integer|min:5',
            'preguntas.*.puntos' => 'required|integer|min:1',
            'preguntas.*.respuestas' => 'required|array|min:2',
            'preguntas.*.respuesta_correcta' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            $examen = Examen::findOrFail($idexamen);

            $examen->update([
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'vidas' => $data['vidas'],
                'minimo_aprobacion' => $data['minimo_aprobacion'],
            ]);

            // 🧹 Eliminar preguntas anteriores
            foreach ($examen->preguntas as $preg) {
                $preg->respuestas()->delete();
                $preg->delete();
            }

            $total_duracion = 0;

            foreach ($data['preguntas'] as $preg) {
                $pregunta = Pregunta::create([
                    'idexamen' => $examen->idexamen,
                    'texto' => $preg['texto'],
                    'tiempo_segundos' => $preg['tiempo_segundos'],
                    'puntos' => $preg['puntos'],
                ]);

                $total_duracion += $preg['tiempo_segundos'];

                foreach ($preg['respuestas'] as $ri => $respuesta) {
                    Respuesta::create([
                        'idpregunta' => $pregunta->idpregunta,
                        'texto' => $respuesta['texto'],
                        'es_correcta' => $ri === $preg['respuesta_correcta'],
                    ]);
                }
            }

            $examen->update(['duracion_segundos' => $total_duracion]);
            DB::commit();

            $examen->load(['preguntas.respuestas', 'unidad.curso']);
            if ($examen->unidad) {
                $examen->unidad->imagen_url = $examen->unidad->imagen
                    ? asset('storage/' . ltrim($this->cleanPath($examen->unidad->imagen), '/'))
                    : asset('storage/default_image.png');
            }

            return response()->json([
                'ok' => true,
                'message' => 'Examen actualizado correctamente',
                'examen' => $examen,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar el examen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ❌ Eliminar examen
     */
    public function destroy($idexamen)
    {
        $examen = Examen::findOrFail($idexamen);
        $examen->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Examen eliminado correctamente',
        ]);
    }

    /**
     * 🧹 Limpieza de ruta para asset()
     */
    private function cleanPath($path)
    {
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }
}
