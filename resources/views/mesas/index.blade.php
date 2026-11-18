@extends('layouts.app')

@section('title', 'Mesas')

@push('head')
<style>
        .mesa-DISPONIBLE { --c: #2e7d32; --bg: #e8f5e9; }
    .mesa-OCUPADA    { --c: #c0392b; --bg: #fdecea; }
    .mesa-RESERVADA  { --c: #a17a00; --bg: #fff4cf; }

    .plano { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 2.5rem 1.5rem; padding: 2rem 1.5rem; background: #fff;
            border: 1px solid #e6dcd0; border-radius: 1rem; }
    .mesa-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .sillas { display: flex; gap: 8px; }
    .silla { width: 24px; height: 12px; border-radius: 6px; background: var(--c); opacity: .5; }
    .mesa-btn { height: 72px; border-radius: 14px; border: 2px solid var(--c); background: var(--bg);
                color: #2b1f1a; font-weight: 600; line-height: 1.2; transition: all .15s; }
    .mesa-btn:hover, .mesa-btn.show { background: var(--c); color: #fff;
                transform: translateY(-3px); box-shadow: 0 6px 14px rgba(0,0,0,.18); }
    .mesa-btn small { display: block; font-weight: 400; font-size: .75rem; }
</style>
@endpush

@section('content')

    <div class="container-fluid px-4 py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <h1 class="h3 mb-0">Mesas</h1>
        <a href="{{ route('inicio') }}" class="small d-inline-block mb-2">← Volver al inicio</a>
        <a href="{{ route('reservas.index') }}" class="small d-inline-block mb-2 ms-3">Ver reservas</a>
        @if (session('rol') === 'ADMINISTRADOR')
            <a href="{{ route('mesas.admin.index') }}" class="small d-inline-block mb-2 ms-3">Administrar mesas</a>
        @endif
        <p class="mb-3">
            <span class="badge" style="background:#2e7d32">Disponible</span>
            <span class="badge" style="background:#c0392b">Ocupada</span>
            <span class="badge" style="background:#a17a00">Reservada</span>
        </p>

            <div class="plano">
                @forelse ($mesas as $mesa)
            <div class="mesa-wrap mesa-{{ $mesa->estado }} dropdown">
                <div class="sillas">
                    @for ($s = 0; $s < ceil($mesa->capacidad / 2); $s++) <span class="silla"></span> @endfor
                </div>

                <button type="button" class="mesa-btn" data-bs-toggle="dropdown" aria-expanded="false"
                        style="width: {{ 90 + $mesa->capacidad * 14 }}px">
                    Mesa {{ $mesa->numero }}
                <small>
                    @if (($mesa->listos ?? 0) > 0)
                        ¡Pedido listo!
                    @else
                        {{ ucfirst(strtolower($mesa->estado)) }}
                    @endif
                </small>
                </button>

                <div class="sillas">
                    @for ($s = 0; $s < floor($mesa->capacidad / 2); $s++) <span class="silla"></span> @endfor
                </div>

                <ul class="dropdown-menu shadow">
                    <li><h6 class="dropdown-header">Mesa {{ $mesa->numero }} · {{ $mesa->capacidad }} puestos</h6></li>
                @if ($mesa->proxima)
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            Reserva: {{ $mesa->proxima->fechaTexto() }} {{ $mesa->proxima->horaTexto() }}<br>
                            {{ $mesa->proxima->nombre_cliente }} · {{ $mesa->proxima->motivo }}
                        </span>
                    </li>
                @endif

                    @if (session('rol') === 'MESERO')
                        <li><a class="dropdown-item" href="{{ route('pedidos.create', $mesa) }}">Nueva comanda</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('pedidos.index', $mesa) }}">Ver pedidos</a></li>
                    @if (session('rol') === 'ADMINISTRADOR' && $mesa->estado === 'OCUPADA')
                    <li><a class="dropdown-item fw-semibold" href="{{ route('caja.show', $mesa) }}">Cobrar</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('reservas.create', $mesa) }}">Reservar</a></li>
                    @if ($mesa->estado !== 'DISPONIBLE')
                        <li>
                            <form method="POST" action="{{ route('mesas.liberar', $mesa) }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Liberar</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </div>
                @empty
            <p class="text-muted">No hay mesas registradas.</p>
                @endforelse
        </div>
    </div>
@endsection