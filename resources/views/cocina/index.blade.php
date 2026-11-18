@extends('layouts.app')

@section('title', 'Cocina')

@push('head')
<meta http-equiv="refresh" content="20">
@endpush

@section('content')

    <div class="container-fluid px-4 py-4">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h1 class="h3 m-0">Cocina</h1>
            <span class="text-muted small">Se actualiza sola cada 20 segundos</span>
        </div>

        @php
            $columnas = [
                'PENDIENTE'      => ['titulo' => 'Nuevos',          'color' => 'warning', 'boton' => 'Empezar'],
                'EN_PREPARACION' => ['titulo' => 'En preparación',  'color' => 'info',    'boton' => 'Marcar listo'],
                'LISTO'          => ['titulo' => 'Listos',          'color' => 'success', 'boton' => null],
            ];
        @endphp

        <div class="row g-3">
            @foreach ($columnas as $estado => $col)
                @php $lista = $pedidos->where('estado', $estado); @endphp
                <div class="col-lg-4">
                    <h2 class="h5">
                        {{ $col['titulo'] }}
                        <span class="badge text-bg-{{ $col['color'] }}">{{ $lista->count() }}</span>
                    </h2>

                    @forelse ($lista as $pedido)
                        @php $minutos = (int) $pedido->fecha_hora->diffInMinutes(now()); @endphp
                        <div class="card mb-3 {{ $estado !== 'LISTO' && $minutos >= 15 ? 'border-danger border-2' : '' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="h4 mb-0">Mesa {{ $pedido->mesa->numero ?? '—' }}</div>
                                        <small class="text-muted">Pedido N.º {{ $pedido->numero }} · {{ $pedido->usuario->nombre ?? '—' }}</small>
                                    </div>
                                    <span class="badge text-bg-light border">hace {{ $minutos }} min</span>
                                </div>

                                <ul class="list-group list-group-flush mt-2">
                                    @foreach ($pedido->detalles->whereNull('id_detalle_padre') as $detalle)
                                        <li class="list-group-item px-0">
                                            <div class="d-flex justify-content-between">
                                                <span>{{ $detalle->descripcion() }}</span>
                                                <strong>x{{ $detalle->cantidad }}</strong>
                                            </div>
                                            @foreach ($detalle->adicionales as $extra)
                                                <div class="small text-success ps-3">
                                                    + {{ $extra->plato->nombre ?? 'Adicional' }}@if ($extra->cantidad > 1) x{{ $extra->cantidad }}@endif
                                                </div>
                                            @endforeach
                                            @if ($detalle->observaciones)
                                                <div class="small fw-semibold text-danger ps-3">⚠ {{ $detalle->observaciones }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>

                                @if ($col['boton'])
                                    <form method="POST" action="{{ route('cocina.avanzar', $pedido) }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="desde" value="{{ $estado }}">
                                        <button type="submit" class="btn btn-delicias w-100">{{ $col['boton'] }}</button>
                                    </form>
                                @else
                                    <div class="text-success small mt-3">Esperando al mesero</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Nada por aquí.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
@endsection