<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoProfesor;
use App\Models\TipoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoProfesorController extends Controller
{
    /**
     * 📋 Listar todos los pagos (pendientes o completados)
     */
    public function index()
    {
        $pagos = PagoProfesor::with([
            'profesor.usuario:idusuario,nombres,apellidos,foto',
            'licencia.curso:idcurso,nombre',
            // ✅ corregido: columna existente en facturas
            'factura:idfactura,idpago_profesor,total,estado'
        ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $pagos
        ]);
    }

    /**
     * 📄 Mostrar los pagos pendientes.
     */
    public function pendientes()
    {
        $pendientes = PagoProfesor::with([
            'profesor.usuario:idusuario,nombres,apellidos,foto',
            'licencia.curso:idcurso,nombre',
            // ✅ corregido también aquí
            'factura:idfactura,idpago_profesor,total,estado'
        ])
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $pendientes
        ]);
    }

    /**
     * 💰 Registrar un nuevo pago pendiente (manual)
     */
    public function store(Request $request)
    {
        $request->validate([
            'idprofesor' => 'required|exists:profesores,idprofesor',
            'idlicencia' => 'required|exists:licencias,idlicencia',
            'monto' => 'required|numeric|min:0',
        ]);

        $pago = PagoProfesor::create([
            'idprofesor' => $request->idprofesor,
            'idlicencia' => $request->idlicencia,
            'monto' => $request->monto,
            'estado' => 'pendiente',
            'fecha_generacion' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Pago pendiente registrado correctamente.',
            'data' => $pago
        ]);
    }

    /**
     * 🔍 Ver detalle de un pago específico.
     */
    public function show($id)
    {
        $pago = PagoProfesor::with([
            'profesor.usuario:idusuario,nombres,apellidos,foto',
            'licencia.curso:idcurso,nombre',
            // ✅ corregido aquí también
            'factura:idfactura,idpago_profesor,total,estado'
        ])->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => $pago
        ]);
    }

    /**
     * 🧾 Listar métodos de pago disponibles desde la BD
     */
    public function metodosPago()
    {
        $tipos = TipoPago::select('idtipo_pago', 'nombre', 'descripcion')->get();

        return response()->json([
            'ok' => true,
            'data' => $tipos
        ]);
    }

    /**
     * 💳 Confirmar pago de un profesor (usando método de pago existente)
     */
    public function confirmarPago(Request $request, $idpago)
    {
        $request->validate([
            'idtipo_pago' => 'required|exists:tipos_pagos,idtipo_pago',
            'referencia' => 'nullable|string|max:100',
        ]);

        $pago = PagoProfesor::with(['profesor.usuario', 'licencia'])->findOrFail($idpago);

        if ($pago->estado === 'pagado') {
            return response()->json([
                'ok' => false,
                'message' => 'Este pago ya fue marcado como completado.'
            ], 400);
        }

        DB::transaction(function () use ($pago, $request) {
            // 🔹 Buscar tipo de pago para obtener el nombre legible
            $tipoPago = TipoPago::find($request->idtipo_pago);

            // 1️⃣ Marcar como pagado (guardando nombre del método legible)
            $pago->update([
                'estado' => 'pagado',
                'metodo_pago' => $tipoPago->nombre, // ✅ guarda texto legible
                'referencia' => $request->referencia,
                'fecha_pago' => now(),
            ]);

            // 2️⃣ Generar factura automática (usando el idtipo_pago real)
            $pago->generarFacturaAutomatica($request->idtipo_pago);
        });

        // 3️⃣ Cargar la factura generada junto a relaciones
        $pago->load(['factura.tipoPago', 'profesor.usuario']);

        return response()->json([
            'ok' => true,
            'message' => 'Pago confirmado y factura generada correctamente.',
            'data' => $pago
        ]);
    }

    /**
     * 🗑️ Eliminar o cancelar un pago.
     */
    public function destroy($idpago)
    {
        $pago = PagoProfesor::findOrFail($idpago);

        if ($pago->estado === 'pagado') {
            return response()->json([
                'ok' => false,
                'message' => 'No se puede eliminar un pago ya completado.'
            ], 400);
        }

        $pago->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Pago eliminado correctamente.'
        ]);
    }
}
