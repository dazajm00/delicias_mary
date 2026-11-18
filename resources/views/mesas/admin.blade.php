@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container py-4">
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
            <h1 class="h3 m-0">Administrar mesas</h1>
            <a href="{{ route('mesas.index') }}" class="small">← Volver al plano de mesas</a>
        </div>

        <div class="row g-3 mb-4">
            <!-- Nueva mesa: el número lo asigna el sistema -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted">Nueva mesa</h2>
                        @if ($siguiente)
                            <form method="POST" action="{{ route('mesas.admin.store') }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label small">Número de mesa</label>
                                    <input type="text" value="Mesa {{ $siguiente }}" class="form-control" readonly disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Puestos</label>
                                    <input type="number" name="capacidad" min="1" max="20" required value="{{ old('capacidad', 4) }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-delicias w-100">Agregar mesa</button>
                                </div>
                            </form>
                            <div class="form-text">El número se asigna solo: es el primero libre entre 1 y {{ $maximo }}.</div>
                        @else
                            <p class="mb-0 text-muted">
                                Ya están usados todos los números del 1 al {{ $maximo }}.
                                Activa una mesa desactivada o aumenta las mesas del local.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Cuántas mesas caben en el local -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted">Mesas del local</h2>
                        <form method="POST" action="{{ route('mesas.admin.limite') }}">
                            @csrf
                            <label class="form-label small">Máximo de mesas</label>
                            <div class="input-group">
                                <input type="number" name="maximo" min="1" max="100" required value="{{ old('maximo', $maximo) }}" class="form-control">
                                <button type="submit" class="btn btn-outline-secondary">Guardar</button>
                            </div>
                        </form>
                        <div class="form-text">
                            Activas: {{ $mesas->where('activa', true)->count() }} de {{ $maximo }}.
                            Ninguna mesa puede tener un número mayor a este.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h5">Mesas</h2>
        <div class="card">
            <div class="list-group list-group-flush">
                @forelse ($mesas as $mesa)
                    <div class="list-group-item {{ $mesa->activa ? '' : 'bg-light' }}">
                        <form method="POST" action="{{ route('mesas.admin.update', $mesa) }}" class="row g-2 align-items-center">
                            @csrf
                            @method('PUT')

                            <div class="col-6 col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Mesa</span>
                                    <input type="number" name="numero" min="1" max="{{ $maximo }}" required value="{{ $mesa->numero }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="capacidad" min="1" max="20" required value="{{ $mesa->capacidad }}" class="form-control">
                                    <span class="input-group-text">puestos</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                @if (! $mesa->activa)
                                    <span class="badge text-bg-secondary">Desactivada</span>
                                @else
                                    <span class="badge text-bg-light border">{{ ucfirst(strtolower($mesa->estado)) }}</span>
                                @endif
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Guardar</button>
                                @if ($mesa->activa)
                                    <button type="submit" form="quitar{{ $mesa->id_mesa }}" class="btn btn-sm btn-outline-danger">Quitar</button>
                                @else
                                    <button type="submit" form="activar{{ $mesa->id_mesa }}" class="btn btn-sm btn-outline-success">Activar</button>
                                @endif
                            </div>
                        </form>

                        <form id="quitar{{ $mesa->id_mesa }}" method="POST" action="{{ route('mesas.admin.destroy', $mesa) }}"
                            onsubmit="return confirm('¿Quitar la mesa {{ $mesa->numero }}? Si ya tiene historial, solo se desactivará.')">
                            @csrf
                            @method('DELETE')
                        </form>
                        <form id="activar{{ $mesa->id_mesa }}" method="POST" action="{{ route('mesas.admin.activar', $mesa) }}">
                            @csrf
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Aún no hay mesas.</div>
                @endforelse
            </div>
        </div>
        <p class="text-muted small mt-2">
            Una mesa que ya tuvo pedidos, cobros o reservas no se borra: queda desactivada y se puede volver a activar.
        </p>
    </div>
@endsection