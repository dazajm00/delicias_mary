@extends('layouts.app')

@section('title', 'Caja')

@section('content')

    @php
        $dinero = fn ($valor) => '$' . number_format($valor, 0, ',', '.');
    @endphp

    <div class="container-fluid px-4 py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h3 m-0">Caja</h1>
                <a href="{{ route('inicio') }}" class="small">← Volver al inicio</a>
            </div>
            <a href="{{ route('caja.cierres') }}" class="btn btn-outline-secondary">Historial de cierres</a>
        </div>

        <!-- ============ ESTADO DE LA CAJA ============ -->
        @if (! $caja)
            <div class="card border-2 border-warning mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-6">
                            <h2 class="h5 mb-1">La caja está cerrada</h2>
                            <p class="text-muted mb-0">
                                Ábrela con el efectivo que hay en el cajón para empezar a cobrar.
                                @if ($ultimoCierre)
                                    <br>Último cierre: {{ $ultimoCierre->cerrada_en->format('d/m/Y h:i a') }} ·
                                    <a href="{{ route('caja.cierre', $ultimoCierre) }}">ver arqueo</a>
                                @endif
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <form method="POST" action="{{ route('caja.abrir') }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-sm-7">
                                    <label for="base" class="form-label small">Base inicial (efectivo en el cajón)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="base" id="base" min="0" step="50" required
                                            value="{{ old('base', $baseSugerida) }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <button type="submit" class="btn btn-delicias w-100">Abrir caja</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php
                $sePuedeCancelar = $pagos->isEmpty() && $movimientos->isEmpty();
                $corrigiendo     = $errors->has('base_nueva') || $errors->has('motivo_correccion');
            @endphp
            <div class="card border-2 border-success mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h2 class="h5 mb-1">
                                Caja abierta <span class="badge text-bg-success align-middle">Abierta</span>
                            </h2>
                            <div class="text-muted small">
                                Desde las {{ $caja->abierta_en->format('h:i a') }} del {{ $caja->abierta_en->format('d/m/Y') }}
                                · Abrió: {{ $caja->usuarioApertura->nombre ?? '—' }}
                                · Base: {{ $dinero($caja->base) }}
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#corregir-base" aria-expanded="{{ $corrigiendo ? 'true' : 'false' }}">
                                Corregir base
                            </button>
                            @if ($sePuedeCancelar)
                                <form method="POST" action="{{ route('caja.cancelar') }}" class="m-0"
                                    onsubmit="return confirm('¿Cancelar la apertura? La caja quedará como si nunca se hubiera abierto.')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">Cancelar apertura</button>
                                </form>
                            @endif
                            <a href="{{ route('caja.cerrar.form') }}" class="btn btn-outline-danger">Cerrar caja</a>
                        </div>
                    </div>

                    <!-- Formulario para corregir la base (oculto hasta que se pide) -->
                    <div class="collapse {{ $corrigiendo ? 'show' : '' }}" id="corregir-base">
                        <form method="POST" action="{{ route('caja.corregir') }}" class="row g-2 align-items-end border-top pt-3 mt-3">
                            @csrf
                            <div class="col-md-3">
                                <label for="base_nueva" class="form-label small">Base correcta</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="base_nueva" id="base_nueva" min="0" step="50" required
                                        value="{{ old('base_nueva', (int) $caja->base) }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="motivo_correccion" class="form-label small">¿Por qué la corriges?</label>
                                <input type="text" name="motivo_correccion" id="motivo_correccion" maxlength="255" required
                                    value="{{ old('motivo_correccion') }}" class="form-control"
                                    placeholder="Ej. me equivoqué al digitar la base">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-delicias w-100">Guardar corrección</button>
                            </div>
                            <div class="col-12 small text-muted">
                                La corrección queda registrada y aparece en el comprobante del cierre.
                            </div>
                        </form>
                    </div>

                    @if ($correcciones->isNotEmpty())
                        <div class="border-top pt-2 mt-3 small">
                            @foreach ($correcciones as $correccion)
                                <div class="text-muted">
                                    Base corregida de {{ $dinero($correccion->base_anterior) }} a {{ $dinero($correccion->base_nueva) }}
                                    · {{ $correccion->fecha_hora->format('h:i a') }}
                                    · {{ $correccion->usuario->nombre ?? '—' }}
                                    · «{{ $correccion->motivo }}»
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- ============ MESAS POR COBRAR ============ -->
        <h2 class="h5">Mesas por cobrar</h2>
        <div class="row g-3 mb-4">
            @forelse ($porCobrar as $mesa)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-danger border-2">
                        <div class="card-body">
                            <h3 class="h5 mb-0">Mesa {{ $mesa->numero }}</h3>
                            <small class="text-muted">{{ $mesa->por_cobrar }} {{ $mesa->por_cobrar === 1 ? 'pedido' : 'pedidos' }}</small>
                            <p class="fs-4 fw-semibold my-2">{{ $dinero($mesa->total_por_cobrar) }}</p>
                            @if ($caja)
                                <a href="{{ route('caja.show', $mesa) }}" class="btn btn-delicias w-100">Cobrar</a>
                            @else
                                <button type="button" class="btn btn-secondary w-100" disabled>Abre la caja para cobrar</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted">No hay mesas con pedidos por cobrar.</p>
            @endforelse
        </div>

        @if ($caja)
            <div class="row g-3 mb-4">
                <!-- ============ EFECTIVO EN CAJA ============ -->
                <div class="col-lg-5">
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
                            <p class="text-muted small mt-2 mb-0">Nequi y tarjeta no entran al cajón: se revisan en el cierre.</p>
                        </div>
                    </div>
                </div>

                <!-- ============ REGISTRAR ENTRADA O SALIDA ============ -->
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Registrar entrada o salida de efectivo</h2>
                            <form method="POST" action="{{ route('caja.movimiento') }}" class="row g-2">
                                @csrf
                                <div class="col-12">
                                    <div class="btn-group w-100" role="group" aria-label="Tipo de movimiento">
                                        <input type="radio" class="btn-check" name="tipo" id="tipo-salida" value="SALIDA"
                                            {{ old('tipo', 'SALIDA') === 'SALIDA' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger" for="tipo-salida">Salida (sale plata del cajón)</label>
                                        <input type="radio" class="btn-check" name="tipo" id="tipo-entrada" value="ENTRADA"
                                            {{ old('tipo') === 'ENTRADA' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success" for="tipo-entrada">Entrada (entra plata al cajón)</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="valor" class="form-label small">Valor</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="valor" id="valor" min="50" step="50" required
                                            value="{{ old('valor') }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="motivo" class="form-label small">Motivo</label>
                                    <input type="text" name="motivo" id="motivo" maxlength="255" required list="motivos-caja"
                                        value="{{ old('motivo') }}" class="form-control" placeholder="Ej. compra de hielo">
                                    <datalist id="motivos-caja">
                                        <option value="Compra de hielo">
                                        <option value="Compra de gas">
                                        <option value="Compra de insumos">
                                        <option value="Pago a domiciliario">
                                        <option value="Monedas para cambio">
                                    </datalist>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-delicias">Registrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ VENTAS DEL TURNO ============ -->
            <h2 class="h5">Ventas de este turno</h2>
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-success border-2">
                        <div class="card-body">
                            <div class="text-muted small">Total cobrado</div>
                            <div class="fs-3 fw-semibold text-success">{{ $dinero($totalVentas) }}</div>
                            <div class="text-muted small">{{ $pagos->count() }} {{ $pagos->count() === 1 ? 'cobro' : 'cobros' }}</div>
                        </div>
                    </div>
                </div>
                @foreach ($porMedio as $nombre => $valor)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-muted small">{{ $nombre }}</div>
                                <div class="fs-4 fw-semibold">{{ $dinero($valor) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">Cobros</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Factura</th>
                                        <th>Hora</th>
                                        <th>Mesa</th>
                                        <th>Medio</th>
                                        <th class="text-end">Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pagos as $pago)
                                        <tr>
                                            <td>N.º {{ $pago->numero }}</td>
                                            <td>{{ $pago->fecha_hora->format('h:i a') }}</td>
                                            <td>Mesa {{ $pago->mesa->numero ?? '—' }}</td>
                                            <td>{{ $pago->medio->nombre ?? '—' }}</td>
                                            <td class="text-end">{{ $dinero($pago->total) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('caja.factura', $pago) }}" class="btn btn-sm btn-outline-secondary">Ver factura</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay cobros en este turno.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">Entradas y salidas de efectivo</div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Motivo</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($movimientos as $movimiento)
                                        <tr>
                                            <td>{{ $movimiento->fecha_hora->format('h:i a') }}</td>
                                            <td>
                                                {{ $movimiento->motivo }}
                                                <div class="small text-muted">{{ $movimiento->usuario->nombre ?? '—' }}</div>
                                            </td>
                                            <td class="text-end fw-semibold {{ $movimiento->tipo === 'ENTRADA' ? 'text-success' : 'text-danger' }}">
                                                {{ $movimiento->tipo === 'ENTRADA' ? '+' : '−' }}{{ $dinero($movimiento->valor) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">Sin entradas ni salidas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
