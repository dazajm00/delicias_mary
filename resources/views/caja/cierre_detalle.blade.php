@extends('layouts.app')

@section('title', 'Detalle del turno')

@section('content')

    @php
        $dinero = fn ($valor) => '$' . number_format($valor, 0, ',', '.');
    @endphp

    <div class="container-fluid px-4 py-4">
        <div class="mb-3">
            <h1 class="h3 m-0">Detalle del turno · Cierre N.º {{ $caja->id_caja }}</h1>
            <a href="{{ route('caja.cierre', $caja) }}" class="small">← Volver al cierre</a>
        </div>

        <div class="text-muted small mb-3">
            Apertura: {{ $caja->abierta_en->format('d/m/Y h:i a') }} · {{ $caja->usuarioApertura->nombre ?? '—' }}
            &nbsp;|&nbsp;
            Cierre: {{ $caja->cerrada_en->format('d/m/Y h:i a') }} · {{ $caja->usuarioCierre->nombre ?? '—' }}
        </div>

        <!-- ============ COBROS ============ -->
        <div class="card mb-4">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span>Cobros</span>
                <span>{{ $pagos->count() }} {{ $pagos->count() === 1 ? 'cobro' : 'cobros' }} · {{ $dinero($pagos->sum('total')) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Factura</th>
                            <th>Hora</th>
                            <th>Mesa</th>
                            <th>Cobró</th>
                            <th>Medio</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Recibido</th>
                            <th class="text-end">Vueltas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pagos as $pago)
                            <tr>
                                <td>N.º {{ $pago->numero }}</td>
                                <td>{{ $pago->fecha_hora->format('h:i a') }}</td>
                                <td>Mesa {{ $pago->mesa->numero ?? '—' }}</td>
                                <td>{{ $pago->usuario->nombre ?? '—' }}</td>
                                <td>{{ $pago->medio->nombre ?? '—' }}</td>
                                <td class="text-end">{{ $dinero($pago->total) }}</td>
                                <td class="text-end">{{ $dinero($pago->monto_recibido) }}</td>
                                <td class="text-end">{{ $dinero($pago->cambio) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('caja.factura', $pago) }}" class="btn btn-sm btn-outline-secondary">Ver factura</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No hubo cobros en este turno.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-4">
            <!-- ============ PRODUCTOS VENDIDOS ============ -->
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Productos vendidos</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($productos as $producto)
                                    <tr>
                                        <td>{{ $producto['nombre'] }}</td>
                                        <td class="text-end">{{ $producto['cantidad'] }}</td>
                                        <td class="text-end">{{ $dinero($producto['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">No se vendieron productos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ ENTRADAS, SALIDAS Y CORRECCIONES ============ -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">Entradas y salidas de efectivo</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hora</th>
                                    <th>Motivo</th>
                                    <th>Registró</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movimientos as $movimiento)
                                    <tr>
                                        <td>{{ $movimiento->fecha_hora->format('h:i a') }}</td>
                                        <td>{{ $movimiento->motivo }}</td>
                                        <td>{{ $movimiento->usuario->nombre ?? '—' }}</td>
                                        <td class="text-end fw-semibold {{ $movimiento->tipo === 'ENTRADA' ? 'text-success' : 'text-danger' }}">
                                            {{ $movimiento->tipo === 'ENTRADA' ? '+' : '−' }}{{ $dinero($movimiento->valor) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Sin entradas ni salidas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($correcciones->isNotEmpty())
                    <div class="card">
                        <div class="card-header fw-semibold">Correcciones de la base</div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Corrigió</th>
                                        <th class="text-end">De</th>
                                        <th class="text-end">A</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($correcciones as $correccion)
                                        <tr>
                                            <td>{{ $correccion->fecha_hora->format('h:i a') }}</td>
                                            <td>{{ $correccion->usuario->nombre ?? '—' }}</td>
                                            <td class="text-end">{{ $dinero($correccion->base_anterior) }}</td>
                                            <td class="text-end">{{ $dinero($correccion->base_nueva) }}</td>
                                            <td>{{ $correccion->motivo }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
