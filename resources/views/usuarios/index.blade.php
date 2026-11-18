@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')

    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 m-0">Usuarios</h1>
                <a href="{{ route('inicio') }}" class="small">← Volver al inicio</a>
            </div>
            <a href="{{ route('usuarios.create') }}" class="btn btn-delicias">+ Nuevo usuario</a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario->nombre }}</td>
                                <td>{{ $usuario->usuario }}</td>
                                <td>{{ ucfirst(strtolower($usuario->rol->nombre)) }}</td>
                                <td>
                                    @if (! $usuario->activo)
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @elseif ($usuario->bloqueado)
                                        <span class="badge text-bg-danger">Bloqueado</span>
                                    @else
                                        <span class="badge text-bg-success">Activo</span>
                                    @endif
                                </td>
                                                                <td class="text-end">
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>

                                    @if ($usuario->id_usuario != session('id_usuario'))
                                        <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline"
                                            onsubmit="return confirm('{{ $usuario->activo ? '¿Desactivar este usuario? No podrá iniciar sesión.' : '¿Activar este usuario?' }}')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="d-inline"
                                            onsubmit="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection