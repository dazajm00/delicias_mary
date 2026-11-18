@extends('layouts.app')

@section('title', 'Cobrar · Mesa ' . $mesa->numero)

@push('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    .btn-check:checked + .btn-outline-dark { background: #5f3524; border-color: #5f3524; color: #fff; }
</style>
@endpush

@section('content')

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h1 class="h3 m-0">Cobrar · Mesa {{ $mesa->numero }}</h1>
            <a href="{{ route('caja.index') }}">← Volver a caja</a>
        </div>

        @if ($sinEntregar > 0)
            <div class="alert alert-warning">
                Hay {{ $sinEntregar }} {{ $sinEntregar === 1 ? 'pedido que aún no está entregado' : 'pedidos que aún no están entregados' }}.
                Podrás cobrar la mesa cuando el mesero los entregue.
            </div>
        @endif

        <div class="row g-4">
            <!-- ============ CONSUMO DE LA MESA ============ -->
            <div class="col-lg-7">
                @foreach ($pedidos as $pedido)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <strong>Pedido N.º {{ $pedido->numero }}</strong>
                                <small class="text-muted">
                                    {{ $pedido->fecha_hora->format('h:i a') }} ·
                                    {{ ucfirst(strtolower(str_replace('_', ' ', $pedido->estado))) }}
                                </small>
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
                                            <span>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 0, ',', '.') }}</span>
                                        </div>
                                        @foreach ($detalle->adicionales as $extra)
                                            <div class="d-flex justify-content-between small text-success ps-3">
                                                <span>+ {{ $extra->plato->nombre ?? 'Adicional' }}@if ($extra->cantidad > 1) x{{ $extra->cantidad }}@endif</span>
                                                <span>${{ number_format($extra->cantidad * $extra->precio_unitario, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>

                            <div class="text-end fw-semibold mt-2">
                                Subtotal: ${{ number_format($pedido->total(), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- ============ COBRO ============ -->
            <div class="col-lg-5">
                <div class="position-sticky" style="top: 1rem">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Total a cobrar</div>
                            <div class="display-5 fw-semibold mb-3">${{ number_format($total, 0, ',', '.') }}</div>

                            @if ($errors->any())
                                <div class="alert alert-danger py-2">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('caja.cobrar', $mesa) }}" id="form-cobro">
                                @csrf

                                <div class="fw-semibold mb-2">Medio de pago</div>
                                <div class="row g-2 mb-3">
                                    @foreach ($medios as $medio)
                                        @php
                                            $nombre = strtolower($medio->nombre);
                                            $icono  = ['efectivo' => 'bi-cash-coin', 'tarjeta' => 'bi-credit-card', 'nequi' => 'bi-phone'][$nombre] ?? 'bi-wallet2';
                                        @endphp
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="id_medio_pago"
                                                id="medio{{ $medio->id_medio_pago }}" value="{{ $medio->id_medio_pago }}"
                                                data-efectivo="{{ $nombre === 'efectivo' ? 1 : 0 }}"
                                                data-nequi="{{ $nombre === 'nequi' ? 1 : 0 }}"
                                                {{ old('id_medio_pago') == $medio->id_medio_pago ? 'checked' : '' }}>
                                            <label class="btn btn-outline-dark w-100 py-3" for="medio{{ $medio->id_medio_pago }}">
                                                <i class="bi {{ $icono }} fs-2 d-block"></i>
                                                {{ $medio->nombre }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="bloque-efectivo" class="d-none mb-3">
                                    <label for="monto_recibido" class="form-label fw-semibold">Monto recibido</label>
                                    <div class="input-group input-group-lg mb-2">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="monto_recibido" id="monto_recibido" min="0" step="500"
                                            value="{{ old('monto_recibido') }}" class="form-control">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btn-exacto">Monto exacto</button>
                                    <div id="texto-vueltas" class="fs-4 fw-semibold"></div>
                                </div>
                                
                                <div id="bloque-nequi" class="d-none mb-3">
                                    <div class="border rounded p-3 bg-light text-center">
                                        <div class="text-muted small">Enviar el pago por Nequi al número</div>
                                        @if ($nequi)
                                            <div class="fs-2 fw-semibold">{{ substr($nequi, 0, 3) }} {{ substr($nequi, 3, 3) }} {{ substr($nequi, 6) }}</div>
                                            <div class="text-muted small">Valor: ${{ number_format($total, 0, ',', '.') }}</div>
                                        @else
                                            <div class="text-danger">Aún no hay un número de Nequi configurado.<br>Agrégalo en Menú y precios.</div>
                                        @endif
                                    </div>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="confirmar-nequi">
                                        <label class="form-check-label" for="confirmar-nequi">Confirmo que el pago ya llegó</label>
                                    </div>
                                </div>

                                <button type="submit" id="btn-cobrar" class="btn btn-delicias btn-lg w-100" disabled>Cobrar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        </div>
@endsection

@push('scripts')
<script>
        const total          = {{ (float) $total }};
        const sinEntregar    = {{ $sinEntregar > 0 ? 'true' : 'false' }};
        const radios         = document.querySelectorAll('input[name="id_medio_pago"]');
        const bloqueEfectivo = document.getElementById('bloque-efectivo');
        const bloqueNequi    = document.getElementById('bloque-nequi');
        const confirmarNequi = document.getElementById('confirmar-nequi');
        const recibido       = document.getElementById('monto_recibido');
        const texto          = document.getElementById('texto-vueltas');
        const boton          = document.getElementById('btn-cobrar');

        const dinero = (n) => '$' + Math.round(n).toLocaleString('es-CO');

        function actualizar() {
            const elegido    = document.querySelector('input[name="id_medio_pago"]:checked');
            const esEfectivo = elegido && elegido.dataset.efectivo === '1';
            const esNequi    = elegido && elegido.dataset.nequi === '1';

            bloqueEfectivo.classList.toggle('d-none', !esEfectivo);
            bloqueNequi.classList.toggle('d-none', !esNequi);

            // Con pedidos sin entregar no se puede cobrar todavía
            if (sinEntregar) {
                boton.disabled = true;
                return;
            }

            // Sin medio elegido no se puede cobrar
            if (!elegido) {
                boton.disabled = true;
                return;
            }

            // Nequi: hay que confirmar que el pago llegó
            if (esNequi) {
                boton.disabled = !confirmarNequi.checked;
                return;
            }

            // Tarjeta u otro medio: se cobra el valor exacto
            if (!esEfectivo) {
                boton.disabled = false;
                return;
            }

            // Efectivo: se calculan las vueltas
            const monto = parseFloat(recibido.value) || 0;
            const resta = monto - total;

            if (resta >= 0) {
                texto.textContent = 'Vueltas: ' + dinero(resta);
                texto.className = 'fs-4 fw-semibold text-success';
                boton.disabled = false;
            } else {
                texto.textContent = monto > 0 ? 'Faltan ' + dinero(-resta) : 'Escribe el monto recibido';
                texto.className = 'fs-5 text-danger';
                boton.disabled = true;
            }
        }

        radios.forEach((radio) => radio.addEventListener('change', actualizar));
        recibido.addEventListener('input', actualizar);
        confirmarNequi.addEventListener('change', actualizar);
        document.getElementById('btn-exacto').addEventListener('click', () => {
            recibido.value = total;
            actualizar();
        });

        actualizar();
    </script>
@endpush