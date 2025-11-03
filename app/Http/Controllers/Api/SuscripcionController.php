<?php

namespace App\Http\Controllers\Api;

use App\Exports\SuscripcionesExport;
use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Suscripcion;
use App\Models\TipoPago;
use App\Models\TipoPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SuscripcionController extends Controller
{
    /**
     * 📋 Listar las suscripciones del usuario autenticado (Estudiante)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $estudiante = $user->estudiante;
        if (!$estudiante) {
            return response()->json(['message' => 'El usuario no es estudiante'], 422);
        }

        $hoy = Carbon::today();

        // 🔄 Actualizar estados vencidos
        $suscripciones = Suscripcion::where('idestudiante', $estudiante->idestudiante)->get();
        foreach ($suscripciones as $suscripcion) {
            if (Carbon::parse($suscripcion->fecha_fin)->lt($hoy) && (int) $suscripcion->estado === 1) {
                $suscripcion->estado = 0; // expirada
                $suscripcion->save();
            }
        }

        // 🔁 Recargar relaciones
        $suscripciones = Suscripcion::with(['plan', 'factura.plan'])
            ->where('idestudiante', $estudiante->idestudiante)
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return response()->json($suscripciones, 200);
    }

    /**
     * 💳 Procesar pago: crear factura y suscripción enlazada
     */
    public function pagar(Request $request)
    {
        $validated = $request->validate([
            'idplan'         => 'required|exists:tipo_planes,idplan',
            'idtipo_pago'    => 'required|exists:tipos_pagos,idtipo_pago',
            'nit'            => 'nullable|string|max:20',
            'razon_social'   => 'nullable|string|max:120',
            'nombre_factura' => 'nullable|string|max:150',
        ]);

        $user = $request->user();
        $estudiante = $user->estudiante;

        if (!$estudiante) {
            Log::error("❌ Usuario {$user->idusuario} no tiene estudiante asociado");

            return response()->json(['message' => 'El usuario no es estudiante'], 422);
        }

        $plan = TipoPlan::findOrFail($validated['idplan']);
        $tipoPago = TipoPago::findOrFail($validated['idtipo_pago']);

        return DB::transaction(function () use ($user, $estudiante, $plan, $tipoPago, $validated) {
            $fechaInicio = Carbon::now();
            $fechaFin = (clone $fechaInicio)->addMonths($plan->duracion ?? 1);

            // 🧾 Crear factura
            $factura = Factura::create([
                'idusuario'      => $user->idusuario,
                'tipo'           => 'suscripcion',
                'idplan'         => $plan->idplan,
                'idlicencia'     => null,
                'idtipo_pago'    => $tipoPago->idtipo_pago,
                'total'          => $plan->precio,
                'moneda'         => 'BOB',
                'referencia'     => 'SUS-' . strtoupper(uniqid()),
                'nit'            => $validated['nit'] ?? null,
                'razon_social'   => $validated['razon_social'] ?? null,
                'nombre_factura' => $validated['nombre_factura'] ?? null,
                'estado'         => 'pagada',
            ]);

            // 🧩 Crear suscripción
            $suscripcion = Suscripcion::create([
                'idestudiante' => $estudiante->idestudiante,
                'idplan'       => $plan->idplan,
                'factura_id'   => $factura->idfactura,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin'    => $fechaFin->toDateString(),
                'estado'       => 1, // activa
            ]);

            // 👤 Actualizar usuario
            $user->update([
                'suscripcion_activa' => true,
                'fecha_fin'          => $fechaFin->toDateString(),
            ]);

            // 🔄 Recargar factura con relaciones
            $factura = Factura::with(['usuario', 'plan', 'tipoPago', 'suscripcion.plan'])
                ->find($factura->idfactura);

            return response()->json([
                'message'     => '✅ Suscripción y factura creadas con éxito',
                'factura'     => $factura,
                'suscripcion' => $suscripcion,
                'user'        => $user,
            ], 201);
        });
    }

    /**
     * 🔍 Ver detalle de una suscripción
     */
    public function show($idsus)
    {
        $suscripcion = Suscripcion::with(['plan', 'factura.plan'])->find($idsus);

        if (!$suscripcion) {
            return response()->json(['message' => 'Suscripción no encontrada'], 404);
        }

        return response()->json($suscripcion, 200);
    }

    // ============================================================
    // 📊 NUEVAS FUNCIONES PARA EL PANEL ADMINISTRATIVO
    // ============================================================

    /**
     * 📋 Listar todas las suscripciones (modo administrador)
     */
    public function adminIndex(Request $request)
    {
        $query = Suscripcion::with(['plan', 'factura.usuario', 'factura.plan'])
            ->orderBy('fecha_inicio', 'desc');

        // 🔎 Filtrar tipo de plan
        if ($request->filled('tipo')) {
            $query->whereHas('plan', function ($q) use ($request) {
                $q->where('nombre', $request->tipo);
            });
        }

        // 🔎 Filtrar estado
        if ($request->filled('estado')) {
            $estado = $request->estado === 'ACTIVA' ? 1 : 0;
            $query->where('estado', $estado);
        }

        // 🔎 Fechas
        if ($request->filled('desde')) {
            $query->whereDate('fecha_inicio', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_fin', '<=', $request->hasta);
        }

        $suscripciones = $query->get()->map(function ($sus) {
            return [
                'idsuscripcion' => $sus->idsuscripcion,
                'tipo' => $sus->plan?->nombre ?? '—',
                'usuario' => $sus->factura?->usuario,
                'plan' => $sus->plan,
                'total' => $sus->factura?->total ?? 0,
                'estado' => $sus->estado ? 'ACTIVA' : 'EXPIRADA',
                'fecha_inicio' => $sus->fecha_inicio,
                'fecha_fin' => $sus->fecha_fin,
            ];
        });

        return response()->json($suscripciones, 200);
    }

    /**
     * 📤 Exportar suscripciones filtradas a Excel
     */
    public function exportExcel(Request $request)
    {
        $nombreArchivo = 'suscripciones_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SuscripcionesExport($request->all()), $nombreArchivo);
    }
}
