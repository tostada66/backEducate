<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Contenido;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContenidoController extends Controller
{
    /**
     * 📂 Listar contenidos de una clase
     */
    public function index($idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->orderBy('orden')
            ->get();

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

        return response()->json($contenidos);
    }

    /**
     * ➕ Crear contenido
     */
    public function store(Request $request, $idcurso, $idunidad, $idclase)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable (revisión / oferta / pendiente).
        // 👉 OJO: AQUÍ ya NO se bloquea por "publicado"
        if (in_array($curso->estado, [
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes agregar contenidos mientras el curso esté en revisión o pendiente de aceptación'
            ], 403);
        }

        $clase = Clase::findOrFail($idclase);

        $data = $request->validate([
            'titulo'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:204800',      // 200MB
            'miniatura'   => 'nullable',                      // puede ser file o string
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        $contenido = new Contenido();
        $contenido->idclase     = $clase->idclase;
        $contenido->titulo      = $data['titulo'];
        $contenido->descripcion = $data['descripcion'] ?? null;
        $contenido->duracion    = $data['duracion'] ?? null;
        $contenido->estado      = $data['estado'] ?? 'borrador';

        // 📂 Calcular orden
        $maxOrden = Contenido::where('idclase', $clase->idclase)->max('orden');
        $contenido->orden = $maxOrden ? $maxOrden + 1 : 1;

        // 📂 Guardar archivo principal
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
            } elseif (str_starts_with($mime, 'video/')) {
                if ($this->yaHayVideoActivo($clase->idclase)) {
                    return response()->json([
                        'error' => '⚠️ Solo se permite un video activo por clase'
                    ], 422);
                }
                $contenido->tipo = 'video';
            } else {
                $contenido->tipo = 'documento';
            }
        } else {
            $contenido->url  = $data['url'] ?? null;
            $contenido->tipo = $data['url'] ? 'documento' : 'otro';
        }

        // 📌 Miniatura si es video
        if ($contenido->tipo === 'video') {
            if ($request->hasFile('miniatura')) {
                $contenido->miniatura = $request->file('miniatura')
                    ->store('contenidos/miniaturas', 'public');
            } elseif (is_string($request->input('miniatura'))) {
                $contenido->miniatura = $request->input('miniatura');
            }
            // estado de procesamiento inicial
            $contenido->estado_proceso = 'pendiente';
        } else {
            $contenido->miniatura = null;
        }

        $contenido->save();

        // ⚙️ Procesamiento de video: duracion + sprites + VTT
        if ($contenido->tipo === 'video') {
            // Completar duración si viene vacía
            if (empty($contenido->duracion) && $contenido->url) {
                $contenido->duracion = $this->probeDuration($contenido->url);
            }

            // Generar miniaturas/sprites y VTT
            $contenido->estado_proceso = 'procesando';
            $contenido->save();

            $thumbInfo = $this->generateVideoThumbnailsAndVtt($contenido->url, $contenido->idcontenido);

            if ($thumbInfo) {
                $contenido->thumb_vtt       = $thumbInfo['vtt'];
                $contenido->thumb_sprite_w  = $thumbInfo['w'];
                $contenido->thumb_sprite_h  = $thumbInfo['h'];
                $contenido->estado_proceso  = 'listo';
                $contenido->procesado_en    = now();
                $contenido->error_proceso   = null;
            } else {
                $contenido->estado_proceso = 'fallo';
                $contenido->error_proceso  = 'No se pudieron generar sprites/VTT.';
            }

            $contenido->save();
        }

        return response()->json($this->mapUrls($contenido), 201);
    }

    /**
     * 👁 Mostrar un contenido
     */
    public function show($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        return response()->json($this->mapUrls($contenido));
    }

    /**
     * ✏️ Actualizar contenido
     */
    public function update(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable (revisión / oferta / pendiente).
        // 👉 Aquí también sacamos "publicado" para permitir flujo de edición simple
        if (in_array($curso->estado, [
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes modificar contenidos mientras el curso esté en revisión o pendiente de aceptación'
            ], 403);
        }

        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:180',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|string|max:255',
            'archivo'     => 'nullable|file|max:204800',
            'miniatura'   => 'nullable',                      // file o string
            'duracion'    => 'nullable|integer|min:1',
            'estado'      => 'in:borrador,publicado'
        ]);

        if (isset($data['titulo'])) {
            $contenido->titulo = $data['titulo'];
        }
        if (isset($data['descripcion'])) {
            $contenido->descripcion = $data['descripcion'];
        }
        if (isset($data['duracion'])) {
            $contenido->duracion = $data['duracion'];
        }
        if (isset($data['estado'])) {
            $contenido->estado = $data['estado'];
        }

        // Archivo principal
        if ($request->hasFile('archivo')) {
            if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
                Storage::disk('public')->delete($contenido->url);
            }

            $file = $request->file('archivo');
            $path = $file->store('contenidos', 'public');
            $contenido->url = $path;

            $mime = strtolower($file->getClientMimeType());

            if (str_starts_with($mime, 'image/')) {
                $contenido->tipo = 'imagen';
                // limpiar atributos de video
                $this->clearVideoArtifacts($contenido);
            } elseif (str_starts_with($mime, 'video/')) {
                if ($this->yaHayVideoActivo($idclase, $contenido->idcontenido)) {
                    return response()->json([
                        'error' => '⚠️ Solo se permite un video activo por clase'
                    ], 422);
                }
                $contenido->tipo = 'video';
            } else {
                $contenido->tipo = 'documento';
                $this->clearVideoArtifacts($contenido);
            }
        } elseif (isset($data['url'])) {
            $contenido->url  = $data['url'];
            $contenido->tipo = $data['url'] ? 'documento' : 'otro';
            if ($contenido->tipo !== 'video') {
                $this->clearVideoArtifacts($contenido);
            }
        }

        // Miniatura
        if ($contenido->tipo === 'video') {
            if ($request->hasFile('miniatura')) {
                if ($contenido->miniatura && Storage::disk('public')->exists($contenido->miniatura)) {
                    Storage::disk('public')->delete($contenido->miniatura);
                }
                $contenido->miniatura = $request->file('miniatura')
                    ->store('contenidos/miniaturas', 'public');
            } elseif (is_string($request->input('miniatura'))) {
                $contenido->miniatura = $request->input('miniatura');
            }
        } else {
            $contenido->miniatura = null;
        }

        $contenido->save();

        // Si ahora es video, re-procesar (si se cambió archivo o no hay VTT)
        if ($contenido->tipo === 'video' && $contenido->url) {
            if (empty($contenido->duracion)) {
                $contenido->duracion = $this->probeDuration($contenido->url);
            }

            $contenido->estado_proceso = 'procesando';
            $contenido->save();

            $thumbInfo = $this->generateVideoThumbnailsAndVtt($contenido->url, $contenido->idcontenido);

            if ($thumbInfo) {
                $contenido->thumb_vtt       = $thumbInfo['vtt'];
                $contenido->thumb_sprite_w  = $thumbInfo['w'];
                $contenido->thumb_sprite_h  = $thumbInfo['h'];
                $contenido->estado_proceso  = 'listo';
                $contenido->procesado_en    = now();
                $contenido->error_proceso   = null;
            } else {
                $contenido->estado_proceso = 'fallo';
                $contenido->error_proceso  = 'No se pudieron generar sprites/VTT.';
            }

            $contenido->save();
        }

        return response()->json($this->mapUrls($contenido));
    }

    /**
     * 🔄 Cambiar orden de un contenido
     */
    public function cambiarOrden(Request $request, $idcurso, $idunidad, $idclase, $idcontenido)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable (revisión / oferta / pendiente).
        // 👉 Igual: "publicado" se permite, porque es parte del flujo de edición simple
        if (in_array($curso->estado, [
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes reordenar contenidos mientras el curso esté en revisión o pendiente de aceptación'
            ], 403);
        }

        $direccion = $request->input('direccion');
        $contenido = Contenido::where('idclase', $idclase)->findOrFail($idcontenido);

        if ($direccion === 'up') {
            $anterior = Contenido::where('idclase', $idclase)
                ->where('orden', $contenido->orden - 1)
                ->first();
            if ($anterior) {
                $contenido->orden = -1;
                $contenido->save();
                $anterior->orden++;
                $anterior->save();
                $contenido->orden = $anterior->orden - 1;
                $contenido->save();
            }
        } elseif ($direccion === 'down') {
            $siguiente = Contenido::where('idclase', $idclase)
                ->where('orden', $contenido->orden + 1)
                ->first();
            if ($siguiente) {
                $contenido->orden = -1;
                $contenido->save();
                $siguiente->orden--;
                $siguiente->save();
                $contenido->orden = $siguiente->orden + 1;
                $contenido->save();
            }
        }

        $contenidos = Contenido::where('idclase', $idclase)
            ->orderBy('orden')
            ->get();

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

        return response()->json([
            'ok' => true,
            'contenidos' => $contenidos
        ]);
    }

    /**
     * 🗑 Eliminar contenido
     */
    public function destroy($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $curso = Curso::findOrFail($idcurso);

        // ❌ Bloqueo si curso no editable (revisión / oferta / pendiente).
        // 👉 Ya no se bloquea por "publicado": ahí aplicamos regla especial solo para video
        if (in_array($curso->estado, [
            'en_revision',
            'oferta_enviada',
            'pendiente_aceptacion'
        ])) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar contenidos mientras el curso esté en revisión o pendiente de aceptación'
            ], 403);
        }

        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        // 🚫 Regla especial: si el curso está publicado, NO se puede borrar el video principal.
        if ($curso->estado === 'publicado' && $contenido->tipo === 'video') {
            return response()->json([
                'ok'      => false,
                'message' => 'Este contenido es el video principal de una clase en un curso publicado. No puede eliminarse, solo editarse.'
            ], 422);
        }

        if ($contenido->url && Storage::disk('public')->exists($contenido->url)) {
            Storage::disk('public')->delete($contenido->url);
        }
        if ($contenido->miniatura && Storage::disk('public')->exists($contenido->miniatura)) {
            Storage::disk('public')->delete($contenido->miniatura);
        }

        // limpiar sprites/vtt
        $this->deleteThumbArtifacts($contenido->idcontenido);

        $contenido->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Contenido eliminado correctamente'
        ]);
    }

    /**
     * 🎓 Catálogo de contenidos (solo publicados)
     */
    public function catalogo($idcurso, $idunidad, $idclase)
    {
        $clase = Clase::findOrFail($idclase);

        $contenidos = $clase->contenidos()
            ->where('estado', 'publicado')
            ->orderBy('orden')
            ->get();

        $contenidos->transform(fn ($c) => $this->mapUrls($c));

        return response()->json($contenidos);
    }

    /**
     * 📥 Descargar contenido (descarga directa)
     */
    public function descargar($idcurso, $idunidad, $idclase, $idcontenido)
    {
        $contenido = Contenido::where('idclase', $idclase)
            ->findOrFail($idcontenido);

        if (!$contenido->url || !Storage::disk('public')->exists($contenido->url)) {
            return response()->json([
                'ok' => false,
                'message' => 'Archivo no encontrado'
            ], 404);
        }

        // ABS sin usar ->path()
        $path = storage_path('app/public/' . ltrim($contenido->url, '/'));
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        $nombre = ($contenido->titulo ?: 'contenido') . '.' . $ext;

        return response()->download($path, $nombre, [
            'Content-Type' => mime_content_type($path)
        ]);
    }

    /**
     * ▶️ Stream con soporte de rangos (206 Partial Content)
     * GET /api/stream/{path}
     */
    public function stream(Request $request, string $path)
    {
        // Seguridad básica
        $relative = ltrim($path, '/');
        $absolute = storage_path('app/public/' . $relative);

        if (!is_file($absolute)) {
            return response()->json(['ok' => false, 'message' => 'Archivo no encontrado'], 404);
        }

        $mime = @mime_content_type($absolute) ?: 'application/octet-stream';
        $size = filesize($absolute);
        $range = $request->header('Range');

        $headers = [
            'Content-Type'  => $mime,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];

        if (!$range) {
            $headers['Content-Length'] = $size;

            return new StreamedResponse(function () use ($absolute) {
                $chunk = 1024 * 1024;
                $fp = fopen($absolute, 'rb');
                while (!feof($fp)) {
                    echo fread($fp, $chunk);
                    @ob_flush(); flush();
                }
                fclose($fp);
            }, 200, $headers);
        }

        if (!preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            return response('', 416, ['Content-Range' => "bytes */{$size}"]);
        }

        $start = $m[1] === '' ? 0 : (int)$m[1];
        $end   = $m[2] === '' ? ($size - 1) : (int)$m[2];

        if ($start > $end || $end >= $size) {
            return response('', 416, ['Content-Range' => "bytes */{$size}"]);
        }

        $length = $end - $start + 1;

        $headers['Content-Length'] = $length;
        $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";

        return new StreamedResponse(function () use ($absolute, $start, $length) {
            $chunk = 1024 * 1024;
            $sent  = 0;
            $fp = fopen($absolute, 'rb');
            fseek($fp, $start);

            while (!feof($fp) && $sent < $length) {
                $toRead = min($chunk, $length - $sent);
                echo fread($fp, $toRead);
                $sent += $toRead;
                @ob_flush(); flush();
            }
            fclose($fp);
        }, 206, $headers);
    }

    /**
     * 🔧 Mapear URLs públicas (archivo, miniatura, vtt)
     * - El archivo del video se sirve por la ruta de stream (rangos).
     */
    private function mapUrls($contenido)
    {
        // Archivo principal -> streamer con rangos
        $path = $this->cleanPath($contenido->url);
        $urlPublica = $path ? route('media.stream', ['path' => ltrim($path, '/')]) : null;
        $contenido->archivo     = $urlPublica;
        $contenido->url_publica = $urlPublica;

        // Miniatura (sirve directo desde /storage)
        if ($contenido->miniatura) {
            $miniPath = $this->cleanPath($contenido->miniatura);
            $contenido->miniatura_publica = $miniPath
                ? asset('storage/' . ltrim($miniPath, '/'))
                : $contenido->miniatura;
        } else {
            $contenido->miniatura_publica = null;
        }

        // VTT/Sprites (sirven directo desde /storage)
        if (!empty($contenido->thumb_vtt)) {
            $vttPath = $this->cleanPath($contenido->thumb_vtt);
            $contenido->thumb_vtt_publica = $vttPath
                ? asset('storage/' . ltrim($vttPath, '/'))
                : $contenido->thumb_vtt;
        } else {
            $contenido->thumb_vtt_publica = null;
        }

        return $contenido;
    }

    private function cleanPath($path)
    {
        if (!$path) return null;
        return str_replace([url('storage') . '/', config('app.url') . '/storage/'], '', $path);
    }

    /**
     * 🧹 Limpiar artefactos de thumbnails de un contenido
     */
    private function deleteThumbArtifacts(int $idcontenido): void
    {
        $dir = "contenidos/thumbs/{$idcontenido}";
        if (Storage::disk('public')->exists($dir)) {
            $files = Storage::disk('public')->allFiles($dir);
            foreach ($files as $f) {
                Storage::disk('public')->delete($f);
            }
            Storage::disk('public')->deleteDirectory($dir);
        }
    }

    /**
     * 🧭 Helpers para rutas y FFmpeg/FFprobe
     */
    private function publicPathToAbsolute(string $relative): string
    {
        // Convierte 'contenidos/archivo.mp4' a /.../storage/app/public/contenidos/archivo.mp4
        return storage_path('app/public/' . ltrim($relative, '/'));
    }

    private function bin(string $name): string
    {
        // Lee FFMPEG_BIN / FFPROBE_BIN del .env; si no, usa el nombre (PATH)
        $key = strtoupper($name) . '_BIN';
        $bin = env($key, $name);

        // Sanea la ruta (caracteres peligrosos)
        $bin = escapeshellcmd($bin);

        // Si hay espacios en la ruta (Windows), envuelve en comillas
        if (preg_match('/\s/', $bin)) {
            $bin = '"' . $bin . '"';
        }

        return $bin;
    }

    private function ff(string $tool, string $args): string
    {
        // Construye el comando final (binario + args)
        return $this->bin($tool) . ' ' . $args;
    }

    private function probeDuration(?string $relativePath): ?int
    {
        if (empty($relativePath)) return null;

        $abs = $this->publicPathToAbsolute($relativePath);
        if (!is_file($abs)) return null;

        $cmd = $this->ff('ffprobe',
            '-v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' .
            escapeshellarg($abs)
        );

        $out = @shell_exec($cmd);
        if (!$out) return null;

        $seconds = (int) round(floatval(trim($out)));
        return $seconds > 0 ? $seconds : null;
    }

    private function generateVideoThumbnailsAndVtt(?string $relativePath, int $idcontenido): ?array
    {
        if (empty($relativePath)) return null;

        $absVideo = $this->publicPathToAbsolute($relativePath);
        if (!is_file($absVideo)) return null;

        // Carpeta de salida: storage/app/public/contenidos/thumbs/{id}/
        $baseDir  = "contenidos/thumbs/{$idcontenido}";
        Storage::disk('public')->makeDirectory($baseDir);
        $absOutDir = storage_path('app/public/' . $baseDir);

        // Parámetros del sprite
        $tileW = 160;   // ancho de cada miniatura
        $tileH = 90;    // alto de cada miniatura
        $every = 2;     // segundos entre capturas
        $cols  = 8;     // columnas por sprite

        // 1) Extraer frames cada N segundos
        $tmpFramesDir = $absOutDir . DIRECTORY_SEPARATOR . 'frames';
        if (!is_dir($tmpFramesDir)) @mkdir($tmpFramesDir, 0775, true);

        $cmd1 = $this->ff('ffmpeg',
            '-hide_banner -loglevel error -y ' .
            '-i ' . escapeshellarg($absVideo) . ' ' .
            '-vf ' . escapeshellarg("fps=1/{$every},scale={$tileW}:{$tileH}") . ' ' .
            escapeshellarg($tmpFramesDir . DIRECTORY_SEPARATOR . 'thumb%05d.png')
        );

        @shell_exec($cmd1);

        $frames = glob($tmpFramesDir . DIRECTORY_SEPARATOR . 'thumb*.png');
        if (!$frames || count($frames) === 0) {
            return null;
        }

        // 2) Armar sprites fila a fila (tile=COLS x 1)
        $chunks = array_chunk($frames, $cols);
        $spriteIndex = 0;

        foreach ($chunks as $chunk) {
            $spriteIndex++;
            $spritePng = $absOutDir . DIRECTORY_SEPARATOR . "sprite{$spriteIndex}.png";

            // Construir inputs
            $inputs = '';
            foreach ($chunk as $f) {
                $inputs .= ' -i ' . escapeshellarg($f);
            }

            // Unir en una fila
            $cmd2 = $this->ff('ffmpeg',
                '-hide_banner -loglevel error -y ' .
                $inputs . ' ' .
                '-filter_complex ' . escapeshellarg("tile={$cols}x1") . ' ' .
                escapeshellarg($spritePng)
            );

            @shell_exec($cmd2);
        }

        // 3) Generar VTT
        $vttPathAbs = $absOutDir . DIRECTORY_SEPARATOR . 'thumbs.vtt';
        $relVtt     = $baseDir . '/thumbs.vtt';

        $vtt  = "WEBVTT\n\n";
        $i    = 0;
        $spriteNumber = 0;

        foreach ($chunks as $chunk) {
            $spriteNumber++;
            $spriteRel = $baseDir . "/sprite{$spriteNumber}.png";
            foreach ($chunk as $k => $f) {
                $start = $i * $every;
                $end   = $start + $every;
                $x     = $k * $tileW;
                $y     = 0;

                $vtt .= sprintf(
                    "%s --> %s\n%s#xywh=%d,%d,%d,%d\n\n",
                    $this->formatTime($start),
                    $this->formatTime($end),
                    $spriteRel,
                    $x, $y, $tileW, $tileH
                );
                $i++;
            }
        }
        file_put_contents($vttPathAbs, $vtt);

        // 4) Limpieza temporal
        foreach ($frames as $f) @unlink($f);
        @rmdir($tmpFramesDir);

        return [
            'vtt' => $relVtt,
            'w'   => $tileW,
            'h'   => $tileH,
        ];
    }

    private function formatTime(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d.000', $h, $m, $s);
    }

    /**
     * 🧽 Limpiar campos de artefactos de video cuando el tipo cambia
     */
    private function clearVideoArtifacts(Contenido $contenido): void
    {
        // borrar directorio de thumbs/vtt
        $this->deleteThumbArtifacts($contenido->idcontenido);

        $contenido->miniatura       = null;
        $contenido->duracion        = null;
        $contenido->thumb_vtt       = null;
        $contenido->thumb_sprite_w  = null;
        $contenido->thumb_sprite_h  = null;
        $contenido->estado_proceso  = 'pendiente';
        $contenido->procesado_en    = null;
        $contenido->error_proceso   = null;
    }

    /**
     * 🔒 Lógica: ya existe video activo en la clase
     */
    private function yaHayVideoActivo(int $idclase, ?int $exceptId = null): bool
    {
        $estadosActivos = ['borrador','publicado','en_revision','oferta_enviada','pendiente_aceptacion'];

        $q = Contenido::where('idclase', $idclase)
            ->where('tipo', 'video')
            ->whereIn('estado', $estadosActivos); // solo activos

        if (!is_null($exceptId)) {
            $q->where('idcontenido', '!=', $exceptId);
        }

        // Soft deletes se exclu    en por defecto en Eloquent
        return $q->exists();
    }
}
