@extends('layouts.app')

@section('title', 'Cierre de caja')

@section('content')

    @php
        $dinero = fn ($valor) => '$' . number_format(abs($valor), 0, ',', '.');
        $diferencia = (float) $caja->diferencia;
    @endphp

    <div class="container py-4" style="max-width: 720px">
        @if (session('success'))
            <div class="alert alert-success d-print-none">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <h1 class="h4 mb-0">Las Delicias de Mary</h1>
                    <div class="text-muted">Cierre de caja N.º {{ $caja->id_caja }}</div>
                </div>

                <div class="small text-muted mb-3">
                    Apertura: {{ $caja->abierta_en->format('d/m/Y h:i a') }} · {{ $caja->usuarioApertura->nombre ?? '—' }}<br>
                    Cierre: {{ $caja->cerrada_en->format('d/m/Y h:i a') }} · {{ $caja->usuarioCierre->nombre ?? '—' }}
                </div>

                <!-- Resultado del arqueo -->
                @if (round($diferencia) == 0)
                    <div class="alert alert-success text-center fs-5 fw-semibold mb-3">La caja cuadró</div>
                @elseif ($diferencia < 0)
                    <div class="alert alert-danger text-center fs-5 fw-semibold mb-3">Faltante de {{ $dinero($diferencia) }}</div>
                @else
                    <div class="alert alert-warning text-center fs-5 fw-semibold mb-3">Sobrante de {{ $dinero($diferencia) }}</div>
                @endif

                <h2 class="h6 text-uppercase text-muted">Efectivo</h2>
                <div class="d-flex justify-content-between py-1"><span>Base inicial</span><span>{{ $dinero($caja->base) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span>+ Ventas en efectivo</span><span>{{ $dinero($caja->ventas_efectivo) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span>+ Entradas</span><span>{{ $dinero($caja->entradas) }}</span></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span>− Salidas</span><span>{{ $dinero($caja->salidas) }}</span></div>
                <div class="d-flex justify-content-between pt-2 fw-semibold"><span>Debería haber</span><span>{{ $dinero($caja->esperado) }}</span></div>
                <div class="d-flex justify-content-between py-1 fw-semibold"><span>Contado</span><span>{{ $dinero($caja->contado) }}</span></div>
                <div class="d-flex justify-content-between py-1 fw-semibold {{ $diferencia < 0 ? 'text-danger' : ($diferencia > 0 ? 'text-warning' : 'text-success') }}">
                    <span>Diferencia</span>
                    <span>{{ $diferencia < 0 ? '−' : ($diferencia > 0 ? '+' : '') }}{{ $dinero($diferencia) }}</span>
                </div>
                @if ($caja->nota)
                    <div class="small mt-2"><strong>Nota:</strong> {{ $caja->nota }}</div>
                @endif

                @if ($correcciones->isNotEmpty())
                    <h2 class="h6 text-uppercase text-muted mt-4">Correcciones de la base</h2>
                    @foreach ($correcciones as $correccion)
                        <div class="small py-1">
                            {{ $correccion->fecha_hora->format('h:i a') }} · {{ $correccion->usuario->nombre ?? '—' }}:
                            de {{ $dinero($correccion->base_anterior) }} a {{ $dinero($correccion->base_nueva) }}
                            · «{{ $correccion->motivo }}»
                        </div>
                    @endforeach
                @endif

                <h2 class="h6 text-uppercase text-muted mt-4">Ventas por medio de pago</h2>
                @forelse ($porMedio as $nombre => $valor)
                    <div class="d-flex justify-content-between py-1"><span>{{ $nombre }}</span><span>{{ $dinero($valor) }}</span></div>
                @empty
                    <p class="text-muted small mb-0">No hubo cobros en esta caja.</p>
                @endforelse
                <div class="d-flex justify-content-between pt-2 border-top fw-semibold">
                    <span>Total vendido ({{ $cobros }} {{ $cobros === 1 ? 'cobro' : 'cobros' }})</span>
                    <span>{{ $dinero($porMedio->sum()) }}</span>
                </div>

                @if ($movimientos->count() > 0)
                    <h2 class="h6 text-uppercase text-muted mt-4">Entradas y salidas</h2>
                    @foreach ($movimientos as $movimiento)
                        <div class="d-flex justify-content-between py-1 small">
                            <span>{{ $movimiento->fecha_hora->format('h:i a') }} · {{ $movimiento->motivo }}</span>
                            <span class="{{ $movimiento->tipo === 'ENTRADA' ? 'text-success' : 'text-danger' }}">
                                {{ $movimiento->tipo === 'ENTRADA' ? '+' : '−' }}{{ $dinero($movimiento->valor) }}
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="mt-3 d-flex gap-2 d-print-none">
            <a href="{{ route('caja.cierre.detalle', $caja) }}" class="btn btn-delicias">Ver detalles</a>
            <a href="{{ route('caja.index') }}" class="btn btn-outline-secondary">Volver a caja</a>
            <a href="{{ route('caja.cierres') }}" class="btn btn-outline-secondary">Historial de cierres</a>
        </div>
    </div>
@endsection
