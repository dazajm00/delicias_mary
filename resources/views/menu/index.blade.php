@extends('layouts.app')

@section('title', 'Menú y precios')

@section('content')

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

        <div class="mb-3">
            <h1 class="h3 m-0">Menú y precios</h1>
            <a href="{{ route('inicio') }}" class="small">← Volver al inicio</a>
        </div>

        <div class="row g-3 mb-4">
            <!-- Precio del plato armado -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted">Plato armado (almuerzo)</h2>
                        <form method="POST" action="{{ route('menu.precio') }}">
                            @csrf
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="precio" min="0" step="100" required
                                    value="{{ (int) $precioArmado }}" class="form-control">
                                <button type="submit" class="btn btn-delicias">Guardar</button>
                            </div>
                        </form>
                        <div class="form-text">Aplica a los pedidos nuevos. Los que ya se enviaron conservan su precio.</div>
                        
                        <hr>
                        <h2 class="h6 text-uppercase text-muted">Número de Nequi del restaurante</h2>
                        <form method="POST" action="{{ route('menu.nequi') }}">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="numero" inputmode="numeric" maxlength="10" placeholder="3001234567"
                                    value="{{ old('numero', $nequi) }}" class="form-control">
                                <button type="submit" class="btn btn-delicias">Guardar</button>
                            </div>
                        </form>
                        <div class="form-text">Se muestra en la pantalla de cobro cuando el cliente paga con Nequi.</div>
                    </div>
                </div>
            </div>

            <!-- Nuevo producto -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                                                <h2 class="h6 text-uppercase text-muted">Agregar al menú</h2>
                        <form method="POST" action="{{ route('menu.store') }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label small">Nombre</label>
                                <input type="text" name="nombre" maxlength="100" required value="{{ old('nombre') }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Categoría</label>
                                <select name="categoria" id="nuevo-categoria" class="form-select" required>
                                    <optgroup label="Menú">
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id_categoria }}" {{ old('categoria') == $categoria->id_categoria ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Ingredientes del almuerzo">
                                        <option value="PRINCIPIO" data-ingrediente="1" {{ old('categoria') === 'PRINCIPIO' ? 'selected' : '' }}>Principio</option>
                                        <option value="PROTEINA" data-ingrediente="1" {{ old('categoria') === 'PROTEINA' ? 'selected' : '' }}>Proteína</option>
                                        <option value="ACOMPANAMIENTO" data-ingrediente="1" {{ old('categoria') === 'ACOMPANAMIENTO' ? 'selected' : '' }}>Acompañamiento</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-3" id="nuevo-precio-bloque">
                                <label class="form-label small">Precio</label>
                                <input type="number" name="precio" id="nuevo-precio" min="0" step="100"
                                    value="{{ old('precio') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-delicias w-100">Agregar</button>
                            </div>
                        </form>
                        <div class="form-text">
                            Para un adicional de plato (huevo frito, patacón), elige «Adicionales».
                            Los ingredientes del almuerzo no llevan precio: el almuerzo tiene un precio único.
                        </div>
                </div>
            </div>
        </div>

        <!-- Lista de productos -->
                <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-productos">Productos del menú</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ingredientes">Ingredientes del almuerzo</button>
            </li>
        </ul>

        <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-productos">
        <div class="card">
            <div class="list-group list-group-flush">
                @forelse ($platos as $plato)
                    <div class="list-group-item {{ $plato->activo ? '' : 'bg-light' }}">
                        <form method="POST" action="{{ route('menu.update', $plato) }}" class="row g-2 align-items-center">
                            @csrf
                            @method('PUT')

                            <div class="col-md-3">
                                <input type="text" name="nombre" maxlength="100" required
                                    value="{{ $plato->nombre }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <select name="id_categoria" class="form-select form-select-sm">
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id_categoria }}" {{ $plato->id_categoria == $categoria->id_categoria ? 'selected' : '' }}>
                                            {{ $categoria->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio" min="0" step="100" required
                                        value="{{ (int) $plato->precio }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                                        id="activo{{ $plato->id_plato }}" {{ $plato->activo ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="activo{{ $plato->id_plato }}">Activo</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="disponible" value="1"
                                        id="disp{{ $plato->id_plato }}" {{ $plato->disponible ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="disp{{ $plato->id_plato }}">Disponible hoy</label>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Guardar</button>
                                <button type="submit" form="eliminar{{ $plato->id_plato }}" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </div>
                        </form>

                        <form id="eliminar{{ $plato->id_plato }}" method="POST" action="{{ route('menu.destroy', $plato) }}"
                            onsubmit="return confirm('¿Eliminar «{{ $plato->nombre }}»? Si ya se pidió alguna vez, solo se desactivará.')">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No hay productos registrados.</div>
                @endforelse
            </div>
        </div>
        <p class="text-muted small mt-2">
            <strong>Activo</strong>: el producto hace parte del menú.
            <strong>Disponible hoy</strong>: desmárcalo si se acabó, sin sacarlo del menú.
        </p>
        </div>
        <div class="tab-pane fade" id="tab-ingredientes">
        
        <p class="text-muted small">Son las opciones que el mesero ve al armar un plato. El arroz va incluido siempre y no se administra aquí.</p>

        @php
            $titulos = ['PRINCIPIO' => 'Principios', 'PROTEINA' => 'Proteínas', 'ACOMPANAMIENTO' => 'Acompañamientos'];
        @endphp

        <div class="row g-3">
            @foreach ($titulos as $tipo => $titulo)
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">{{ $titulo }}</div>
                        <div class="list-group list-group-flush">
                            @forelse ($insumos->where('tipo', $tipo) as $insumo)
                                <div class="list-group-item {{ $insumo->activo ? '' : 'bg-light' }}">
                                    <form method="POST" action="{{ route('insumos.update', $insumo) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="nombre" maxlength="100" required
                                            value="{{ $insumo->nombre }}" class="form-control form-control-sm mb-2">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                                                        id="ins-activo{{ $insumo->id_insumo }}" {{ $insumo->activo ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="ins-activo{{ $insumo->id_insumo }}">Activo</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="disponible" value="1"
                                                        id="ins-disp{{ $insumo->id_insumo }}" {{ $insumo->disponible ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="ins-disp{{ $insumo->id_insumo }}">Disponible hoy</label>
                                                </div>
                                            </div>
                                            <div>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Guardar</button>
                                                <button type="submit" form="ins-eliminar{{ $insumo->id_insumo }}" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                            </div>
                                        </div>
                                    </form>

                                    <form id="ins-eliminar{{ $insumo->id_insumo }}" method="POST" action="{{ route('insumos.destroy', $insumo) }}"
                                        onsubmit="return confirm('¿Eliminar «{{ $insumo->nombre }}»? Si ya se pidió alguna vez, solo se desactivará.')">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            @empty
                                <div class="list-group-item text-muted small">Aún no hay ingredientes de este tipo.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        </div>
    </div>
        
@endsection

@push('scripts')
<script>
        // Esconder el precio cuando se agrega un ingrediente
        const selector     = document.getElementById('nuevo-categoria');
        const bloquePrecio = document.getElementById('nuevo-precio-bloque');
        const campoPrecio  = document.getElementById('nuevo-precio');

        function ajustarPrecio() {
            const esIngrediente = selector.selectedOptions[0].dataset.ingrediente === '1';
            bloquePrecio.classList.toggle('d-none', esIngrediente);
            campoPrecio.required = !esIngrediente;
        }
        selector.addEventListener('change', ajustarPrecio);
        ajustarPrecio();

        // Recordar la pestaña después de guardar
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach((boton) => {
            boton.addEventListener('shown.bs.tab', () => sessionStorage.setItem('menuTab', boton.dataset.bsTarget));
        });

        const guardada = location.hash === '#ingredientes' ? '#tab-ingredientes' : sessionStorage.getItem('menuTab');
        if (guardada) {
            const boton = document.querySelector('[data-bs-target="' + guardada + '"]');
            if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
        }
</script>
@endpush
