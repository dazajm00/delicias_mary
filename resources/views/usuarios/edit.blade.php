@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h1 class="h3 mb-3">Editar usuario</h1>

                <div class="card">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $usuario->nombre) }}"
                                    class="form-control @error('nombre') is-invalid @enderror">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario</label>
                                <input type="text" name="usuario" id="usuario" value="{{ old('usuario', $usuario->usuario) }}"
                                    class="form-control @error('usuario') is-invalid @enderror">
                                @error('usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="contrasena" class="form-label">Nueva contraseña</label>
                                <input type="password" name="contrasena" id="contrasena"
                                    class="form-control @error('contrasena') is-invalid @enderror">
                                @error('contrasena') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Déjala vacía para conservar la actual.</div>
                            </div>

                            <div class="mb-3">
                                <label for="id_rol" class="form-label">Rol</label>
                                <select name="id_rol" id="id_rol" class="form-select @error('id_rol') is-invalid @enderror">
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id_rol }}" {{ old('id_rol', $usuario->id_rol) == $rol->id_rol ? 'selected' : '' }}>
                                            {{ ucfirst(strtolower($rol->nombre)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row mb-4">
                                <div class="col-6">
                                    <label for="activo" class="form-label">Cuenta</label>
                                    <select name="activo" id="activo" class="form-select">
                                        <option value="1" {{ old('activo', (int) $usuario->activo) == 1 ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('activo', (int) $usuario->activo) == 0 ? 'selected' : '' }}>Desactivada</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="bloqueado" class="form-label">Bloqueo</label>
                                    <select name="bloqueado" id="bloqueado" class="form-select">
                                        <option value="0" {{ old('bloqueado', (int) $usuario->bloqueado) == 0 ? 'selected' : '' }}>Desbloqueada</option>
                                        <option value="1" {{ old('bloqueado', (int) $usuario->bloqueado) == 1 ? 'selected' : '' }}>Bloqueada</option>
                                    </select>
                                    @if ($usuario->bloqueado)
                                        <div class="form-text text-danger">Bloqueada por {{ $usuario->intentos_fallidos }} intentos fallidos.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-delicias">Guardar cambios</button>
                                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection