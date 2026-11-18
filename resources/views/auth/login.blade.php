<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Las Delicias de Mary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #faf6f1; }
        .panel-marca { background: #2b1f1a; color: #faf6f1; }
        .logo-circulo { width: 36px; height: 36px; border-radius: 50%; background: #b5432a;
                        display: flex; align-items: center; justify-content: center; }
        .btn-delicias { background: #b5432a; border-color: #b5432a; color: #fff; }
        .btn-delicias:hover { background: #973520; border-color: #973520; color: #fff; }
        .campo-icono { position: relative; }
        .campo-icono .bi-izquierda { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8a776b; }
        .campo-icono input { padding-left: 40px; }
        .btn-ojo { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #8a776b; }
        .punto { height: 4px; border-radius: 2px; display: inline-block; }
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none; }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">

            <!-- Panel de la marca (se esconde en pantallas pequeñas) -->
            <div class="col-md-6 d-none d-md-flex flex-column justify-content-between p-5 panel-marca">
                <div class="d-flex align-items-center gap-2">
                    <div class="logo-circulo"><i class="bi bi-egg-fried text-white"></i></div>
                    <span class="fw-semibold">Las Delicias de Mary</span>
                </div>

                <div>
                    <p class="fs-3 fw-medium mb-2">Comida casera,<br>servicio en orden.</p>
                    <p class="mb-0" style="color: #d9c9bd">Mesas, comandas, cocina y caja en un solo lugar.</p>
                </div>

                <div>
                    <span class="punto" style="width: 28px; background: #b5432a"></span>
                    <span class="punto" style="width: 12px; background: #6b4a3a"></span>
                    <span class="punto" style="width: 12px; background: #6b4a3a"></span>
                </div>
            </div>

            <!-- Formulario -->
            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center p-4">
                <div class="w-100" style="max-width: 380px">

                    <!-- Nombre del restaurante, solo en celular -->
                    <div class="d-md-none text-center mb-4">
                        <div class="logo-circulo mx-auto mb-2" style="width: 48px; height: 48px">
                            <i class="bi bi-egg-fried text-white fs-4"></i>
                        </div>
                        <h1 class="h5 mb-0">Las Delicias de Mary</h1>
                    </div>

                    <h2 class="h4 mb-1" style="color: #2b1f1a">Bienvenido</h2>
                    <p class="text-muted mb-4">Ingresa con tu usuario para empezar el turno.</p>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="usuario" class="form-label small text-muted">Usuario</label>
                            <div class="campo-icono">
                                <i class="bi bi-person bi-izquierda"></i>
                                <input type="text" name="usuario" id="usuario" class="form-control"
                                    value="{{ old('usuario') }}" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="contrasena" class="form-label small text-muted">Contraseña</label>
                            <div class="campo-icono">
                                <i class="bi bi-lock bi-izquierda"></i>
                                <input type="password" name="contrasena" id="contrasena" class="form-control pe-5"
                                    required autocomplete="current-password">
                                <button type="button" class="btn-ojo btn btn-sm" id="btn-ojo" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye" id="icono-ojo"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-delicias w-100 py-2">Ingresar</button>
                    </form>

                    <p class="text-center text-muted small mt-4 mb-0">
                        ¿Olvidaste tu contraseña? Pídesela al administrador.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar u ocultar la contraseña
        const campo = document.getElementById('contrasena');
        const icono = document.getElementById('icono-ojo');

        document.getElementById('btn-ojo').addEventListener('click', () => {
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            icono.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    </script>
</body>
</html>