<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * 🧾 Historial general de facturas (Admin, Profesor, Estudiante)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 🔹 ADMIN → ve todas las facturas
        if (($user->rol->nombre ?? null) === 'admin' || ($user->idrol ?? null) === 3) {
            $facturas = Factura::with([
                'usuario:idusuario,nombres,apellidos,foto',
                'plan:idplan,nombre',
                'tipoPago:idtipo_pago,nombre',
                'licencia.curso:idcurso,nombre',
                'pagoProfesor.profesor.usuario:idusuario,nombres,apellidos,foto',
                'pagoProfesor.licencia.curso:idcurso,nombre',
            ])
                ->orderByDesc('created_at')
                ->get();

            // 🔹 PROFESOR → ve solo sus pagos recibidos
        } elseif (($user->rol->nombre ?? null) === 'profesor' || ($user->idrol ?? null) === 2) {
            $facturas = Factura::where('tipo', 'pago_profesor')
                ->whereHas('pagoProfesor.profesor.usuario', function ($q) use ($user) {
                    $q->where('idusuario', $user->idusuario);
                })
                ->with([
                    'pagoProfesor.profesor.usuario:idusuario,nombres,apellidos,foto',
                    'pagoProfesor.licencia.curso:idcurso,nombre',
                    'tipoPago:idtipo_pago,nombre',
                ])
                ->orderByDesc('created_at')
                ->get();

            // 🔹 ESTUDIANTE → ve sus propias facturas
        } else {
            $facturas = Factura::where('idusuario', $user->idusuario)
                ->whereIn('tipo', ['suscripcion', 'licencia'])
                ->with([
                    'plan:idplan,nombre',
                    'tipoPago:idtipo_pago,nombre',
                    'licencia.curso:idcurso,nombre',
                ])
                ->orderByDesc('created_at')
                ->get();
        }

        return response()->json([
            'ok' => true,
            'data' => $facturas
        ]);
    }

    /**
     * 🧾 Crear una nueva factura (para estudiante o profesor)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo'             => 'required|in:suscripcion,licencia,pago_profesor',
            'idplan'           => 'nullable|exists:tipo_planes,idplan',
            'idlicencia'       => 'nullable|exists:licencias,idlicencia',
            'idtipo_pago'      => 'nullable|exists:tipos_pagos,idtipo_pago',
            'idpago_profesor'  => 'nullable|exists:pagos_profesores,idpago',
            'total'            => 'required|numeric|min:0',
            'moneda'           => 'nullable|string|size:3',
            'referencia'       => 'nullable|string|max:100',
            'nit'              => 'nullable|string|max:20',
            'razon_social'     => 'nullable|string|max:120',
            'nombre_factura'   => 'nullable|string|max:150',
        ]);

        $factura = Factura::create([
            'idusuario'       => $request->user()->idusuario,
            'tipo'            => $validated['tipo'],
            'idplan'          => $validated['idplan'] ?? null,
            'idlicencia'      => $validated['idlicencia'] ?? null,
            'idtipo_pago'     => $validated['idtipo_pago'] ?? null,
            'idpago_profesor' => $validated['idpago_profesor'] ?? null,
            'total'           => $validated['total'],
            'moneda'          => $validated['moneda'] ?? 'BOB',
            'referencia'      => $validated['referencia'] ?? 'REF-' . strtoupper(uniqid()),
            'nit'             => $validated['nit'] ?? null,
            'razon_social'    => $validated['razon_social'] ?? null,
            'nombre_factura'  => $validated['nombre_factura'] ?? null,
            'estado'          => 'pagada',
        ]);

        $factura->load(['usuario', 'plan', 'tipoPago']);

        return response()->json([
            'ok' => true,
            'message' => 'Factura generada correctamente.',
            'data' => $factura,
        ], 201);
    }

    /**
     * 🔍 Mostrar detalle de una factura específica
     */
    public function show($idfactura, Request $request)
    {
        $user = $request->user();

        $factura = Factura::with([
            'usuario:idusuario,nombres,apellidos,foto',
            'plan:idplan,nombre,duracion',
            'tipoPago:idtipo_pago,nombre',
            'pagoProfesor.profesor.usuario:idusuario,nombres,apellidos,foto',
            'pagoProfesor.licencia.curso:idcurso,nombre,idprofesor',
            'licencia.curso.profesor.usuario:idusuario,nombres,apellidos,foto',
            // 👇 añadimos la relación suscripción
            'suscripcion:idplan,factura_id,fecha_inicio,fecha_fin,estado'
        ])
            ->where('idfactura', $idfactura)
            ->when(
                ($user->rol->nombre ?? null) !== 'admin' && ($user->idrol ?? null) !== 3,
                function ($q) use ($user) {
                    $q->where('idusuario', $user->idusuario)
                        ->orWhereHas('pagoProfesor.profesor.usuario', function ($sub) use ($user) {
                            $sub->where('idusuario', $user->idusuario);
                        });
                }
            )
            ->first();

        if (!$factura) {
            return response()->json([
                'ok' => false,
                'message' => 'Factura no encontrada o sin permisos para verla.'
            ], 404);
        }

        $cliente = $factura->tipo === 'pago_profesor'
            ? ($factura->pagoProfesor->profesor->usuario ?? null)
            : $factura->usuario;

        return response()->json([
            'ok' => true,
            'data' => array_merge($factura->toArray(), [
                'cliente' => $cliente,
            ]),
        ]);
    }

    /**
     * 💾 Descargar factura como PDF
     */
    public function descargarPdf($idfactura, Request $request)
    {
        $user = $request->user();

        $factura = Factura::with([
            'usuario',
            'plan',
            'tipoPago',
            'pagoProfesor.profesor.usuario',
            'pagoProfesor.licencia.curso',
            'licencia.curso.profesor.usuario',
            'suscripcion'
        ])
            ->where('idfactura', $idfactura)
            ->when(
                ($user->rol->nombre ?? null) !== 'admin' && ($user->idrol ?? null) !== 3,
                function ($q) use ($user) {
                    $q->where('idusuario', $user->idusuario)
                        ->orWhereHas('pagoProfesor.profesor.usuario', function ($sub) use ($user) {
                            $sub->where('idusuario', $user->idusuario);
                        });
                }
            )
            ->first();

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        // 🧠 Seleccionar vista según tipo
        $view = match ($factura->tipo) {
            'pago_profesor' => 'facturas.factura_profesor',
            default => 'facturas.pdf',
        };

        // 📄 Generar PDF
        $pdf = Pdf::loadView($view, compact('factura'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $fileName = "factura_{$factura->idfactura}.pdf";

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
