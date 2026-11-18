@extends('layouts.app')

@section('title', 'Historial de cierres')

@section('content')

    @php
        $dinero = fn ($valor) => '$' . number_format(abs($valor), 0, ',', '.');
    @endphp

    <div class="container-fluid px-4 py-4">
        <div class="mb-3">
            <h1 class="h3 m-0">Historial de cierres de caja</h1>
            <a href="{{ route('caja.index') }}" class="small">← Volver a caja</a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N.º</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-end">Total vendido</th>
                            <th class="text-end">Efectivo esperado</th>
                            <th class="text-end">Contado</th>
                            <th class="text-end">Diferencia</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cajas as $caja)
                            @php $diferencia = (float) $caja->diferencia; @endphp
                            <tr>
                                <td>{{ $caja->id_caja }}</td>
                                <td>
                                    {{ $caja->abierta_en->format('d/m/Y h:i a') }}
                                    <div class="small text-muted">{{ $caja->usuarioApertura->nombre ?? '—' }}</div>
                                </td>
                                <td>
                                    {{ $caja->cerrada_en->format('d/m/Y h:i a') }}
                                    <div class="small text-muted">{{ $caja->usuarioCierre->nombre ?? '—' }}</div>
                                </td>
                                <td class="text-end">{{ $dinero($caja->ventas_efectivo + $caja->ventas_otros) }}</td>
                                <td class="text-end">{{ $dinero($caja->esperado) }}</td>
                                <td class="text-end">{{ $dinero($caja->contado) }}</td>
                                <td class="text-end">
                                    @if (round($diferencia) == 0)
                                        <span class="badge text-bg-success">Cuadró</span>
                                    @elseif ($diferencia < 0)
                                        <span class="badge text-bg-danger">Faltan {{ $dinero($diferencia) }}</span>
                                    @else
                                        <span class="badge text-bg-warning">Sobran {{ $dinero($diferencia) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('caja.cierre', $caja) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aún no hay cierres de caja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $cajas->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
