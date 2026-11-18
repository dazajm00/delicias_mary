@extends('layouts.app')

@section('title', 'Comanda')

@section('content')

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h1 class="h3 m-0">
                @isset($pedido)
                    Editar pedido N.º {{ $pedido->numero }} · Mesa {{ $mesa->numero }}
                @else
                    Comanda · Mesa {{ $mesa->numero }}
                @endisset
                <small class="text-muted fs-6">{{ ucfirst(strtolower($mesa->estado)) }}</small>
            </h1>
            @isset($pedido)
                <a href="{{ route('pedidos.index', $mesa) }}">← Volver a pedidos</a>
            @else
                <a href="{{ route('mesas.index') }}">← Volver a mesas</a>
            @endisset
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>No se pudo guardar la comanda.</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @isset($pedido)
            <form method="POST" action="{{ route('pedidos.update', $pedido) }}">
                @csrf
                @method('PUT')
        @else
            <form method="POST" action="{{ route('pedidos.store', $mesa) }}">
                @csrf
        @endisset
            @csrf

            <div class="row g-4">
                <!-- ============ PLATOS ARMADOS ============ -->
                <div class="col-lg-8">
                    <h2 class="h5">Platos armados <small class="text-muted">(el arroz va incluido en todos)</small></h2>
                        <p class="text-muted small">Agrega un plato por cada pedido de la mesa. Puedes quitar los que no necesites.</p>

                        @php
                            $platosPrevios = old('platos') ? array_keys(old('platos')) : [1];
                        @endphp

                        <div id="lista-platos">
                            @foreach ($platosPrevios as $i)
                                @include('pedidos._plato', ['i' => $i])
                            @endforeach
                        </div>

                        <button type="button" id="btn-agregar-plato" class="btn btn-outline-dark mb-3">+ Agregar plato</button>

                        <template id="plantilla-plato">
                            @include('pedidos._plato', ['i' => '__INDEX__'])
                        </template>
                    </div>

                <!-- ============ MENÚ FIJO ============ -->
                <div class="col-lg-4">
                <div class="position-sticky" style="top: 1rem">

                    <h2 class="h5">Menú del día, bebidas y adicionales</h2>
                    <p class="text-muted small">Usa + y − para agregar o quitar lo que pidan.</p>

                    <div class="card" style="max-height: 40vh; overflow-y: auto">
                        <div class="card-body">
                            @foreach ($categorias as $categoria)
                                @if ($categoria->platos->count() > 0)
                                    <h3 class="h6 text-uppercase text-muted mt-2">{{ $categoria->nombre }}</h3>
                                    @foreach ($categoria->platos as $plato)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                            <div>
                                                {{ $plato->nombre }}<br>
                                                <small class="text-muted">${{ number_format($plato->precio, 0, ',', '.') }}</small>
                                            </div>
                                            <div class="input-group input-group-sm contador" style="width: 110px">
                                                <button type="button" class="btn btn-outline-secondary btn-menos">−</button>
                                                <input type="text" name="productos[{{ $plato->id_plato }}]" readonly
                                                    data-precio="{{ $plato->precio }}" data-nombre="{{ $plato->nombre }}"
                                                    value="{{ old("productos.$plato->id_plato", 0) }}"
                                                    class="form-control text-center bg-white">
                                                <button type="button" class="btn btn-outline-secondary btn-mas">+</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @if ($mesa->estado === 'RESERVADA')
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="confirmar_reserva" value="1" id="confirmar_reserva">
                            <label class="form-check-label" for="confirmar_reserva">
                                El cliente de la reserva ya llegó
                                @if (! empty($reserva))
                                    <br><small class="text-muted">{{ $reserva->nombre_cliente }} · {{ $reserva->horaTexto() }} · {{ $reserva->motivo }}</small>
                                @endif
                            </label>
                        </div>
                    @endif

                        <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Resumen del pedido</h2>
                            <div id="resumen-lineas" class="mb-2"></div>
                            <div class="d-flex justify-content-between border-top pt-2">
                                <strong>Total</strong>
                                <strong id="resumen-total" class="fs-5">$0</strong>
                            </div>
                            <div class="form-text">Un plato cuenta cuando tiene principio y proteína.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-delicias btn-lg w-100 mt-3">{{ isset($pedido) ? 'Guardar cambios' : 'Enviar a cocina' }}</button>
            
                    </div>
                </div>
        </form>
    </div>

            </form>
    </div>

@endsection

@push('scripts')
<script>
    const lista        = document.getElementById('lista-platos');
        const plantilla    = document.getElementById('plantilla-plato').innerHTML;
        const precioArmado = {{ (float) $precioArmado }};
        let contador       = {{ max($platosPrevios) }};

        const dinero = (n) => '$' + Math.round(n).toLocaleString('es-CO');

        function renumerar() {
            lista.querySelectorAll('.plato-card').forEach((tarjeta, pos) => {
                tarjeta.querySelector('.numero-plato').textContent = pos + 1;
            });
        }

        // Texto que muestran los desplegables de acompañamientos y adicionales de cada plato
        function actualizarDesplegables() {
            lista.querySelectorAll('.plato-card').forEach((tarjeta) => {
                const acomp = tarjeta.querySelector('.resumen-acomp');
                if (acomp) {
                    const nombres = [...tarjeta.querySelectorAll('input[name*="[acompanamientos]"]:checked')]
                        .map((casilla) => casilla.dataset.nombre);
                    acomp.textContent = nombres.length ? nombres.join(', ') : 'Sin acompañamientos';
                    acomp.classList.toggle('text-muted', nombres.length === 0);
                }

                const adic = tarjeta.querySelector('.resumen-adic');
                if (adic) {
                    const partes = [];
                    let valor = 0;
                    tarjeta.querySelectorAll('input[name*="[adicionales]"]').forEach((campo) => {
                        const cantidad = parseInt(campo.value) || 0;
                        if (cantidad > 0) {
                            partes.push(campo.dataset.nombre + (cantidad > 1 ? ' x' + cantidad : ''));
                            valor += cantidad * parseFloat(campo.dataset.precio);
                        }
                    });
                    adic.textContent = partes.length ? partes.join(', ') + ' · ' + dinero(valor) : 'Sin adicionales';
                    adic.classList.toggle('text-muted', partes.length === 0);
                }
            });
        }

        function actualizarResumen() {
            actualizarDesplegables();
            const filas = [];
            let total = 0;

            // Platos armados (solo los completos) con sus adicionales
            lista.querySelectorAll('.plato-card').forEach((tarjeta, pos) => {
                const principio = tarjeta.querySelector('select[name$="[principio]"]').value;
                const proteina  = tarjeta.querySelector('select[name$="[proteina]"]').value;
                if (!principio || !proteina) return;

                filas.push({ texto: 'Plato ' + (pos + 1), valor: precioArmado, extra: false });
                total += precioArmado;

                tarjeta.querySelectorAll('input[name*="[adicionales]"]').forEach((campo) => {
                    const cantidad = parseInt(campo.value) || 0;
                    if (cantidad > 0) {
                        const valor = cantidad * parseFloat(campo.dataset.precio);
                        filas.push({ texto: '+ ' + campo.dataset.nombre + (cantidad > 1 ? ' x' + cantidad : ''), valor: valor, extra: true });
                        total += valor;
                    }
                });
            });

            // Menú fijo (especiales, jugos, gaseosas)
            document.querySelectorAll('input[name^="productos["]').forEach((campo) => {
                const cantidad = parseInt(campo.value) || 0;
                if (cantidad > 0) {
                    const valor = cantidad * parseFloat(campo.dataset.precio);
                    filas.push({ texto: campo.dataset.nombre + (cantidad > 1 ? ' x' + cantidad : ''), valor: valor, extra: false });
                    total += valor;
                }
            });

            const cuerpo = document.getElementById('resumen-lineas');
            cuerpo.innerHTML = '';

            if (filas.length === 0) {
                cuerpo.innerHTML = '<p class="text-muted small mb-0">Aún no hay nada en el pedido.</p>';
            }

            filas.forEach((fila) => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between small' + (fila.extra ? ' text-success ps-3' : '');
                const izquierda = document.createElement('span');
                izquierda.textContent = fila.texto;
                const derecha = document.createElement('span');
                derecha.textContent = dinero(fila.valor);
                div.append(izquierda, derecha);
                cuerpo.appendChild(div);
            });

            document.getElementById('resumen-total').textContent = dinero(total);
        }

        // Agregar un plato
        document.getElementById('btn-agregar-plato').addEventListener('click', () => {
            contador++;
            lista.insertAdjacentHTML('beforeend', plantilla.replaceAll('__INDEX__', contador));
            renumerar();
            actualizarResumen();
        });

        // Quitar un plato
        lista.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-quitar-plato')) {
                e.target.closest('.plato-card').remove();
                renumerar();
                actualizarResumen();
            }
        });

        // Cambiar principio o proteína
        lista.addEventListener('change', actualizarResumen);

        // Botones − y +
        document.addEventListener('click', (e) => {
            const boton = e.target.closest('.btn-menos, .btn-mas');
            if (!boton) return;

            const campo = boton.closest('.contador').querySelector('input');
            let valor = parseInt(campo.value) || 0;

            valor += boton.classList.contains('btn-mas') ? 1 : -1;
            campo.value = Math.min(20, Math.max(0, valor));
            actualizarResumen();
        });

        renumerar();
        actualizarResumen();
</script>
@endpush

