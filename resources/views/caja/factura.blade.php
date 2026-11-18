@extends('layouts.app')

@section('title', 'Factura N.º ' . $pago->numero)

@push('head')
<style>
    @media print { .card { border: 0 !important; box-shadow: none !important; } }
</style>
@endpush

@section('content')

    <div class="container py-4" style="max-width: 640px">
        @if (session('success'))
            <div class="alert alert-success d-print-none">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <h1 class="h4 mb-0">Las Delicias de Mary</h1>
                    <div class="text-muted small">Factura N.º {{ $pago->numero }}</div>
                </div>

                <div class="small text-muted mb-3">
                    {{ $pago->fecha_hora->format('d/m/Y h:i a') }} · Mesa {{ $pago->mesa->numero ?? '—' }}<br>
                    Atendió: {{ $pago->usuario->nombre ?? '—' }}
                </div>

                @foreach ($pago->pedidos->sortBy('fecha_hora') as $pedido)
                    @foreach ($pedido->detalles->whereNull('id_detalle_padre') as $detalle)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>
                                @if ($detalle->tipo === 'ARMADO')
                                    Almuerzo
                                    <br><small class="text-muted">{{ $detalle->descripcion() }}</small>
                                @else
                                    {{ $detalle->descripcion() }}
                                    @if ($detalle->cantidad > 1) <span class="text-muted">x{{ $detalle->cantidad }}</span> @endif
                                @endif
                            </span>
                            <span>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 0, ',', '.') }}</span>
                        </div>
                        @foreach ($detalle->adicionales as $extra)
                            <div class="d-flex justify-content-between small ps-3 py-1 border-bottom">
                                <span>+ {{ $extra->plato->nombre ?? 'Adicional' }}@if ($extra->cantidad > 1) x{{ $extra->cantidad }}@endif</span>
                                <span>${{ number_format($extra->cantidad * $extra->precio_unitario, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach

                <div class="d-flex justify-content-between fs-4 fw-semibold mt-3">
                    <span>Total</span>
                    <span>${{ number_format($pago->total, 0, ',', '.') }}</span>
                </div>

                <div class="small mt-3">
                    <div class="d-flex justify-content-between"><span>Medio de pago</span><span>{{ $pago->medio->nombre ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between"><span>Recibido</span><span>${{ number_format($pago->monto_recibido, 0, ',', '.') }}</span></div>
                    <div class="d-flex justify-content-between"><span>Vueltas</span><span>${{ number_format($pago->cambio, 0, ',', '.') }}</span></div>
                </div>

                <p class="text-center text-muted small mt-4 mb-0">¡Gracias por su visita!</p>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('caja.index') }}" class="btn btn-delicias">Volver a caja</a>
        </div>
    </div>
@endsection