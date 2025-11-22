<?php

namespace App\Exports;

use App\Models\Suscripcion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuscripcionesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    /**
     * 📊 Trae la colección de suscripciones filtradas
     */
    public function collection()
    {
        $query = Suscripcion::with(['plan', 'factura.usuario'])
            ->orderBy('fecha_inicio', 'desc');

        // ✅ Filtros aplicados desde la UI
        if (!empty($this->filtros['tipo']) && $this->filtros['tipo'] !== 'null') {
            $query->whereHas('plan', function ($q) {
                $q->where('nombre', $this->filtros['tipo']);
            });
        }

        if (!empty($this->filtros['estado']) && $this->filtros['estado'] !== 'null') {
            $estado = $this->filtros['estado'] === 'ACTIVA' ? 1 : 0;
            $query->where('estado', $estado);
        }

        if (!empty($this->filtros['desde']) && $this->filtros['desde'] !== 'null') {
            $query->whereDate('fecha_inicio', '>=', $this->filtros['desde']);
        }

        if (!empty($this->filtros['hasta']) && $this->filtros['hasta'] !== 'null') {
            $query->whereDate('fecha_fin', '<=', $this->filtros['hasta']);
        }

        $suscripciones = $query->get();

        return $suscripciones->map(function ($s) {
            return [
                'ID'            => $s->id ?? $s->idsuscripcion ?? $s->getKey(), // ✅ garantiza que el ID salga
                'Usuario'       => $s->factura?->usuario
                    ? trim($s->factura->usuario->nombres . ' ' . $s->factura->usuario->apellidos)
                    : '—',
                'Tipo de Plan'  => $s->plan?->nombre ?? '—',
                'Fecha Inicio'  => $s->fecha_inicio
                    ? date('d/m/Y', strtotime($s->fecha_inicio))
                    : '—',
                'Fecha Fin'     => $s->fecha_fin
                    ? date('d/m/Y', strtotime($s->fecha_fin))
                    : '—',
                'Estado'        => $s->estado ? 'ACTIVA' : 'EXPIRADA',
                'Total (Bs)'    => number_format((float) ($s->factura?->total ?? 0), 2, '.', ''),
            ];
        });
    }

    /**
     * 🏷️ Encabezados de columnas
     */
    public function headings(): array
    {
        return [
            'ID',
            'Usuario',
            'Tipo de Plan',
            'Fecha Inicio',
            'Fecha Fin',
            'Estado',
            'Total (Bs)',
        ];
    }
}
