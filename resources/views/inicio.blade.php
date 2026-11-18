@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container py-4">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <h1 class="h3 mb-1">Bienvenido, {{ session('nombre') }}</h1>
        <p class="text-muted mb-4">Rol: {{ ucfirst(strtolower(session('rol'))) }}</p>

        <div class="row g-3">
            @if (session('rol') === 'ADMINISTRADOR')
                {{-- Resumen del día --}}
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-success border-2">
                        <div class="card-body">
                            <div class="text-muted small">Mesas disponibles</div>
                            <div class="display-6 fw-semibold text-success">{{ $mesasDisponibles ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-danger border-2">
                        <div class="card-body">
                            <div class="text-muted small">Mesas ocupadas</div>
                            <div class="display-6 fw-semibold text-danger">{{ $mesasOcupadas ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-warning border-2">
                        <div class="card-body">
                            <div class="text-muted small">Mesas reservadas</div>
                            <div class="display-6 fw-semibold" style="color:#a17a00">{{ $mesasReservadas ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">Pedidos en curso</div>
                            <div class="display-6 fw-semibold">{{ $pedidosEnCurso ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                {{-- Accesos --}}
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Mesas y pedidos</h2>
                            <p class="text-muted">Ver el estado de las mesas y supervisar pedidos.</p>
                            <a href="{{ route('mesas.index') }}" class="btn btn-delicias">Ver mesas</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Menú y precios</h2>
                            <p class="text-muted">Productos, ingredientes y precios.</p>
                            <a href="{{ route('menu.index') }}" class="btn btn-delicias">Gestionar menú</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Usuarios</h2>
                            <p class="text-muted">{{ $usuariosActivos ?? 0 }} activos. Crear, editar y desactivar cuentas.</p>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-delicias">Gestionar usuarios</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 bg-light">
                        <div class="card-body">
                            <h2 class="h5 text-muted">Caja</h2>
                            <p class="text-muted">Cobro y vueltas.</p>
                            <a href="{{ route('caja.index') }}" class="btn btn-delicias">Ir a caja</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('rol') === 'MESERO')
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Mesas y comandas</h2>
                            <p class="text-muted">Ver las mesas y enviar comandas a la cocina.</p>
                            <a href="{{ route('mesas.index') }}" class="btn btn-delicias">Ir a mesas</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('rol') === 'COCINERO')
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Cocina</h2>
                            <p class="text-muted">Ver los pedidos que llegan, empezarlos y marcarlos como listos.</p>
                            <a href="{{ route('cocina.index') }}" class="btn btn-delicias">Ir a la cocina</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection