<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Suscripcion;
use App\Models\Factura;
use App\Models\TipoPlan;
use App\Models\TipoPago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuscripcionController extends Controller
{
    /**
     * Listar las suscripciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $estudiante = $user->estudiante;
        if (!$estudiante) {
            return response()->json(['message' => 'El usuario no es estudiante'], 422);
        }

        $hoy = Carbon::today();

        // 1️⃣ Traer todas las suscripciones del estudiante
        $suscripciones = Suscripcion::where('idestudiante', $estudiante->idestudiante)->get();

        // 2️⃣ Revisar y actualizar estados según fecha_fin
        foreach ($suscripciones as $suscripcion) {
            if (Carbon::parse($suscripcion->fecha_fin)->lt($hoy) && (int) $suscripcion->estado === 1) {
                $suscripcion->estado = 0; // expirada
                $suscripcion->save();
            }
        }

        // 3️⃣ Volver a cargar ya con relaciones y estados actualizados
        $suscripciones = Suscripcion::with(['plan', 'factura.plan'])
            ->where('idestudiante', $estudiante->idestudiante)
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return response()->json($suscripciones, 200);
    }

    /**
     * Procesar pago: crear factura y suscripción enlazada
     */
    public function pagar(Request $request)
    {
        $validated = $request->validate([
            'idplan'         => 'required|exists:tipo_planes,idplan',
            'idpago'         => 'required|exists:tipos_pagos,idpago',
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
        $tipoPago = TipoPago::findOrFail($validated['idpago']);

        return DB::transaction(function () use ($user, $estudiante, $plan, $tipoPago, $validated) {
            // Calcular fechas
            $fechaInicio = Carbon::now();
            $fechaFin = (clone $fechaInicio)->addMonths($plan->duracion ?? 1);

            // 1️⃣ Crear factura
            $factura = Factura::create([
                'idusuario'      => $user->idusuario,
                'tipo'           => 'suscripcion',
                'idplan'         => $plan->idplan,
                'idlicencia'     => null,
                'idpago'         => $tipoPago->idpago,
                'total'          => $plan->precio,
                'moneda'         => 'BOB',
                'referencia'     => 'SUS-' . strtoupper(uniqid()),
                'nit'            => $validated['nit'] ?? null,
                'razon_social'   => $validated['razon_social'] ?? null,
                'nombre_factura' => $validated['nombre_factura'] ?? null,
                'estado'         => 'pagada',
            ]);

            // 2️⃣ Crear suscripción asociada
            $suscripcion = Suscripcion::create([
                'idestudiante' => $estudiante->idestudiante,
                'idplan'       => $plan->idplan,
                'factura_id'   => $factura->idfactura,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin'    => $fechaFin->toDateString(),
                'estado'       => 1, // activa
            ]);

            // 3️⃣ Actualizar usuario con datos de suscripción activa
            $user->update([
                'suscripcion_activa' => true,
                'fecha_fin'          => $fechaFin->toDateString(),
            ]);

            // 4️⃣ Recargar factura con relaciones
            $factura = Factura::with(['usuario', 'plan', 'tipoPago', 'suscripcion.plan'])
                ->find($factura->idfactura);

            return response()->json([
                'message'     => '✅ Suscripción y factura creadas con éxito',
                'factura'     => $factura,
                'suscripcion' => $suscripcion,
                'user'        => $user, // 👉 devuelve el user actualizado
            ], 201);
        });
    }

    /**
     * Ver detalle de una suscripción
     */
    public function show($idsus)
    {
        $suscripcion = Suscripcion::with(['plan', 'factura.plan'])
            ->find($idsus);

        if (!$suscripcion) {
            return response()->json(['message' => 'Suscripción no encontrada'], 404);
        }

        return response()->json($suscripcion, 200);
    }
}
