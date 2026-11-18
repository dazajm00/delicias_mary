@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h1 class="h3 mb-3">Nuevo usuario</h1>

                <div class="card">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('usuarios.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                                    class="form-control @error('nombre') is-invalid @enderror">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario (para iniciar sesión)</label>
                                <input type="text" name="usuario" id="usuario" value="{{ old('usuario') }}"
                                    class="form-control @error('usuario') is-invalid @enderror">
                                @error('usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" name="contrasena" id="contrasena"
                                    class="form-control @error('contrasena') is-invalid @enderror">
                                @error('contrasena') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Mínimo 6 caracteres.</div>
                            </div>

                            <div class="mb-4">
                                <label for="id_rol" class="form-label">Rol</label>
                                <select name="id_rol" id="id_rol" class="form-select @error('id_rol') is-invalid @enderror">
                                    <option value="">Selecciona un rol</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id_rol }}" {{ old('id_rol') == $rol->id_rol ? 'selected' : '' }}>
                                            {{ ucfirst(strtolower($rol->nombre)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-delicias">Guardar</button>
                                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
