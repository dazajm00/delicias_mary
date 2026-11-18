<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Las Delicias de Mary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #faf6f1; }
        .navbar-delicias { background: #2b1f1a; }
        .btn-delicias { background: #b5432a; border-color: #b5432a; color: #fff; }
        .btn-delicias:hover { background: #973520; border-color: #973520; color: #fff; }
        @media print { body { background: #fff; } }
    </style>
    @stack('head')
</head>
<body>
    @php $rol = session('rol'); @endphp

    <!-- Barra superior (la única que existe en todo el proyecto) -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-delicias d-print-none">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold" href="{{ route('inicio') }}">Las Delicias de Mary</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-principal"
                    aria-controls="menu-principal" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu-principal">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">Inicio</a>
                    </li>

                    @if ($rol === 'ADMINISTRADOR')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">Usuarios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('menu.*') ? 'active' : '' }}" href="{{ route('menu.index') }}">Menú y precios</a>
                        </li>
                    @endif

                    @if ($rol === 'ADMINISTRADOR' || $rol === 'MESERO')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('mesas.*', 'pedidos.*') ? 'active' : '' }}" href="{{ route('mesas.index') }}">Mesas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reservas.*') ? 'active' : '' }}" href="{{ route('reservas.index') }}">Reservas</a>
                        </li>
                    @endif

                    @if ($rol === 'ADMINISTRADOR')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('caja.*') ? 'active' : '' }}" href="{{ route('caja.index') }}">Caja</a>
                        </li>
                    @endif

                    @if ($rol === 'COCINERO')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cocina.*') ? 'active' : '' }}" href="{{ route('cocina.index') }}">Cocina</a>
                        </li>
                    @endif

                    <li class="nav-item">
                        <span class="nav-link text-white-50">{{ session('nombre') }} · {{ ucfirst(strtolower($rol)) }}</span>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>