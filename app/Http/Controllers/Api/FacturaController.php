<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FacturaController extends Controller
{
    /**
     * Listar todas las facturas del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $facturas = Factura::with([
                'plan',
                'tipoPago',
                'suscripcion.plan'
            ])
            ->where('idusuario', $user->idusuario)
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($facturas, 200);
    }

    /**
     * Crear una nueva factura
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo'           => 'required|in:suscripcion,licencia',
            'idplan'         => 'nullable|exists:tipo_planes,idplan',
            'idlicencia'     => 'nullable|exists:licencias,idlicencia',
            'idpago'         => 'required|exists:tipos_pagos,idpago',
            'total'          => 'required|numeric|min:0',
            'moneda'         => 'nullable|string|size:3',
            'referencia'     => 'nullable|string|max:100',
            'nit'            => 'nullable|string|max:20',
            'razon_social'   => 'nullable|string|max:120',
            'nombre_factura' => 'nullable|string|max:150',
        ]);

        $factura = Factura::create([
            'idusuario'      => $request->user()->idusuario,
            'tipo'           => $validated['tipo'],
            'idplan'         => $validated['idplan'] ?? null,
            'idlicencia'     => $validated['idlicencia'] ?? null,
            'idpago'         => $validated['idpago'],
            'total'          => $validated['total'],
            'moneda'         => $validated['moneda'] ?? 'BOB',
            'referencia'     => $validated['referencia'] ?? null,
            'nit'            => $validated['nit'] ?? null,
            'razon_social'   => $validated['razon_social'] ?? null,
            'nombre_factura' => $validated['nombre_factura'] ?? null,
            'estado'         => 'pagada',
        ]);

        return response()->json([
            'message' => 'Factura generada correctamente',
            'data'    => $factura,
        ], 201);
    }

    /**
     * Ver el detalle de una factura con relaciones
     */
    public function show($idfactura, Request $request)
    {
        $user = $request->user();

        $factura = Factura::with([
                'plan',
                'tipoPago',
                'suscripcion.plan'
            ])
            ->where('idusuario', $user->idusuario)
            ->find($idfactura);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura, 200);
    }

    /**
     * Descargar factura como PDF
     */
    public function descargarPdf($idfactura, Request $request)
    {
        $user = $request->user();

        $factura = Factura::with([
                'usuario',
                'plan',
                'tipoPago',
                'suscripcion.plan'
            ])
            ->where('idusuario', $user->idusuario)
            ->find($idfactura);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        // ✅ Forzar fuente UTF-8 y DejaVu Sans
        $pdf = Pdf::loadView('facturas.pdf', compact('factura'))
                  ->setPaper('a4')
                  ->setOptions([
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true,
                      'defaultFont' => 'DejaVu Sans'
                  ]);

        // Guardar en storage/app/public/facturas/
        $fileName = "factura_{$factura->idfactura}.pdf";
        $path = "facturas/{$fileName}";

        Storage::disk('public')->put($path, $pdf->output());

        // Actualizar path en la BD
        $factura->update(['pdf_path' => Storage::url($path)]);

        // Retornar el PDF para descarga directa
        return $pdf->download($fileName);
    }
}
