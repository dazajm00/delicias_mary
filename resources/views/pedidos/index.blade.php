@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container-fluid px-4 py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h1 class="h3 m-0">
                Pedidos · Mesa {{ $mesa->numero }}
                <small class="text-muted fs-6">{{ ucfirst(strtolower($mesa->estado)) }}</small>
            </h1>
            <a href="{{ route('mesas.index') }}">← Volver a mesas</a>
        </div>

        <div class="text-end mb-2">
            @if (request('cancelados'))
                <a href="{{ route('pedidos.index', $mesa) }}" class="small">Ocultar cancelados</a>
            @else
                <a href="{{ route('pedidos.index', ['mesa' => $mesa, 'cancelados' => 1]) }}" class="small">Mostrar cancelados</a>
            @endif
        </div>

        @if ($pedidos->count() > 0)
            <div class="alert alert-light border d-flex justify-content-between align-items-center">
                <span>Consumo total de la mesa</span>
                <strong class="fs-5">
                    ${{ number_format(
                        $pedidos->where('estado', '!=', 'CANCELADO')
                                ->sum(fn ($p) => $p->detalles->sum(fn ($d) => $d->cantidad * $d->precio_unitario)),
                        0, ',', '.') }}
                </strong>
            </div>
        @endif

        @forelse ($pedidos as $pedido)
            @php
                // Color del estado del pedido
                $colores = [
                    'PENDIENTE'      => 'warning',
                    'EN_PREPARACION' => 'info',
                    'LISTO'          => 'primary',
                    'ENTREGADO'      => 'success',
                    'CANCELADO'      => 'secondary',
                ];
            @endphp
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h2 class="h5 mb-0">
                                Pedido N.º {{ $pedido->numero }}
                                <span class="badge text-bg-{{ $colores[$pedido->estado] ?? 'secondary' }} fs-6 align-middle">
                                    {{ ['PENDIENTE' => 'Pendiente', 'EN_PREPARACION' => 'En preparación', 'LISTO' => 'Listo', 'ENTREGADO' => 'Entregado', 'CANCELADO' => 'Cancelado'][$pedido->estado] ?? $pedido->estado }}
                                </span>
                            </h2>
                            <small class="text-muted">
                                {{ $pedido->fecha_hora->format('d/m/Y h:i a') }} · Mesero: {{ $pedido->usuario->nombre ?? '—' }}
                            </small>
                        </div>

                        @if ($pedido->estado === 'PENDIENTE')
                            <div class="d-flex gap-2">
                                @if (session('rol') === 'MESERO')
                                    <a href="{{ route('pedidos.edit', $pedido) }}" class="btn btn-outline-secondary">Editar</a>
                                @endif
                                <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}"
                                    onsubmit="return confirm('¿Cancelar este pedido? La cocina ya no lo preparará.')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">Cancelar pedido</button>
                                </form>
                            </div>
                        @endif
                        @if ($pedido->estado === 'LISTO' && session('rol') === 'MESERO')
                            <form method="POST" action="{{ route('pedidos.entregar', $pedido) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">Marcar entregado</button>
                            </form>
                        @endif
                    </div>

                    <ul class="list-group list-group-flush mt-2">
                        @foreach ($pedido->detalles->whereNull('id_detalle_padre') as $detalle)
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        {{ $detalle->descripcion() }}
                                        @if ($detalle->cantidad > 1)
                                            <span class="text-muted">x{{ $detalle->cantidad }}</span>
                                        @endif
                                    </span>
                                    <span>
                                        @if ($detalle->precio_unitario !== null)
                                            ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 0, ',', '.') }}
                                        @endif
                                    </span>
                                </div>

                                @foreach ($detalle->adicionales as $extra)
                                    <div class="d-flex justify-content-between small text-success ps-3">
                                        <span>+ {{ $extra->plato->nombre ?? 'Adicional' }}@if ($extra->cantidad > 1) x{{ $extra->cantidad }}@endif</span>
                                        <span>${{ number_format($extra->precio_unitario * $extra->cantidad, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach

                                @if ($detalle->observaciones)
                                    <div class="small text-muted ps-3">Obs.: {{ $detalle->observaciones }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($pedido->estado !== 'CANCELADO')
                        <div class="text-end fw-semibold mt-2">
                            Total del pedido:
                            ${{ number_format($pedido->detalles->sum(fn ($d) => $d->cantidad * $d->precio_unitario), 0, ',', '.') }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">Esta mesa no tiene pedidos registrados.</p>
        @endforelse
    </div>
</body>
@endsection