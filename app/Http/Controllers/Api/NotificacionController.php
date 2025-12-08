<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * 📥 Lista de notificaciones del usuario autenticado
     * (para la campanita / panel tipo inbox)
     */
    public function index(Request $request)
    {
        $user = $request->user(); // Usuario autenticado (tabla usuarios)

        $notificaciones = Notificacion::where('idusuario', $user->idusuario)
            // Primero no leídas, luego más recientes
            ->orderByRaw('leido_en IS NULL DESC')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notificaciones);
    }

    /**
     * 🔢 Resumen por categoría para los badges del menú lateral
     * Devuelve algo tipo:
     * { "solicitudes": 3, "curso": 1, "pagos": 2 }
     */
    public function resumen(Request $request)
    {
        $user = $request->user();

        $rows = Notificacion::selectRaw('categoria, COUNT(*) as total')
            ->where('idusuario', $user->idusuario)
            ->whereNull('leido_en')
            ->groupBy('categoria')
            ->get();

        $resumen = [];

        foreach ($rows as $row) {
            $resumen[$row->categoria] = (int) $row->total;
        }

        return response()->json($resumen);
    }

    /**
     * ✅ Marcar notificaciones como leídas
     * - Si manda "ids": marca solo esas.
     * - Si NO manda "ids": marca TODAS las del usuario como leídas.
     */
    public function marcarLeidas(Request $request)
    {
        $user = $request->user();

        $ids = $request->input('ids'); // puede ser array o null

        $query = Notificacion::where('idusuario', $user->idusuario);

        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('idnotificacion', $ids);
        }

        $query->update(['leido_en' => now()]);

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Notificaciones marcadas como leídas.',
        ]);
    }
}
