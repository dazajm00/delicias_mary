@extends('layouts.app')

@section('title', 'Cerrar caja')

@section('content')

    @php
        $dinero = fn ($valor) => '$' . number_format($valor, 0, ',', '.');
    @endphp

    <div class="container py-4" style="max-width: 760px">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h1 class="h3 m-0">Cerrar caja</h1>
            <a href="{{ route('caja.index') }}">← Volver a caja</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($mesasPorCobrar > 0)
            <div class="alert alert-warning">
                Hay {{ $mesasPorCobrar }} {{ $mesasPorCobrar === 1 ? 'mesa' : 'mesas' }} con consumo sin cobrar.
                Si cierras ahora, se cobrarán en la próxima caja.
            </div>
        @endif

        <div class="text-muted small mb-3">
            Caja abierta el {{ $caja->abierta_en->format('d/m/Y') }} a las {{ $caja->abierta_en->format('h:i a') }}
            por {{ $caja->usuarioApertura->nombre ?? '—' }}.
        </div>
        @foreach ($correcciones as $correccion)
            <div class="alert alert-secondary py-2 small">
                Base corregida de {{ $dinero($correccion->base_anterior) }} a {{ $dinero($correccion->base_nueva) }}
                a las {{ $correccion->fecha_hora->format('h:i a') }} por {{ $correccion->usuario->nombre ?? '—' }}:
                «{{ $correccion->motivo }}»
            </div>
        @endforeach

        <div class="row g-3">
            <!-- Efectivo -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted">Efectivo en el cajón</h2>
                        <div class="d-flex justify-content-between py-1"><span>Base inicial</span><span>{{ $dinero($efectivo['base']) }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span>+ Ventas en efectivo</span><span>{{ $dinero($efectivo['ventas']) }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span>+ Entradas</span><span class="text-success">{{ $dinero($efectivo['entradas']) }}</span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>− Salidas</span><span class="text-danger">{{ $dinero($efectivo['salidas']) }}</span></div>
                        <div class="d-flex justify-content-between pt-2 fs-5 fw-semibold">
                            <span>Debería haber</span><span>{{ $dinero($efectivo['esperado']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Otros medios -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted">Revisar fuera del cajón</h2>
                        @php $otros = $porMedio->except('Efectivo'); @endphp
                        @forelse ($otros as $nombre => $valor)
                            <div class="d-flex justify-content-between py-1"><span>{{ $nombre }}</span><span class="fw-semibold">{{ $dinero($valor) }}</span></div>
                        @empty
                            <p class="text-muted small mb-0">No hubo cobros con Nequi ni con tarjeta.</p>
                        @endforelse
                        @if ($otros->count() > 0)
                            <p class="text-muted small mt-2 mb-0">Compara estos valores con la app de Nequi y con el cierre del datáfono.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Arqueo -->
        <div class="card mt-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Arqueo</h2>
                <form method="POST" action="{{ route('caja.cerrar') }}" id="form-cierre"
                    onsubmit="return confirm('¿Cerrar la caja? Después no se podrán registrar cobros hasta abrir una nueva.')">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="contado" class="form-label fw-semibold">¿Cuánto efectivo contaste en el cajón?</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">$</span>
                                <input type="number" name="contado" id="contado" min="0" step="50" required
                                    value="{{ old('contado') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="resultado-arqueo" class="fs-5 fw-semibold text-muted">Escribe lo que contaste</div>
                        </div>
                        <div class="col-12">
                            <label for="nota" class="form-label small">Nota (opcional)</label>
                            <input type="text" name="nota" id="nota" maxlength="255" value="{{ old('nota') }}"
                                class="form-control" placeholder="Ej. se dieron mal unas vueltas">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-delicias btn-lg">Cerrar caja</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const esperado  = {{ (float) $efectivo['esperado'] }};
    const contado   = document.getElementById('contado');
    const resultado = document.getElementById('resultado-arqueo');
    const dinero    = (n) => '$' + Math.round(Math.abs(n)).toLocaleString('es-CO');

    function arqueo() {
        if (contado.value === '') {
            resultado.textContent = 'Escribe lo que contaste';
            resultado.className = 'fs-5 fw-semibold text-muted';
            return;
        }
        const diferencia = (parseFloat(contado.value) || 0) - esperado;
        if (Math.round(diferencia) === 0) {
            resultado.textContent = 'La caja cuadra';
            resultado.className = 'fs-5 fw-semibold text-success';
        } else if (diferencia < 0) {
            resultado.textContent = 'Faltan ' + dinero(diferencia);
            resultado.className = 'fs-5 fw-semibold text-danger';
        } else {
            resultado.textContent = 'Sobran ' + dinero(diferencia);
            resultado.className = 'fs-5 fw-semibold text-warning';
        }
    }

    contado.addEventListener('input', arqueo);
    arqueo();
</script>
@endpush
