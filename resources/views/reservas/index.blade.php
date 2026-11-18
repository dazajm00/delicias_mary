@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container-fluid px-4 py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="mb-3">
            <h1 class="h3 m-0">Reservas</h1>
            <a href="{{ route('mesas.index') }}" class="small">← Volver a mesas</a>
            <span class="small text-muted ms-2">Para reservar, elige una mesa y toca «Reservar».</span>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Día</th>
                            <th>Hora</th>
                            <th>Mesa</th>
                            <th>Cliente</th>
                            <th>Personas</th>
                            <th>Motivo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservas as $reserva)
                            <tr>
                                <td>{{ $reserva->fechaTexto() }}</td>
                                <td>{{ $reserva->horaTexto() }}</td>
                                <td>Mesa {{ $reserva->mesa->numero ?? '—' }}</td>
                                <td>{{ $reserva->nombre_cliente }}</td>
                                <td>{{ $reserva->personas }}</td>
                                <td>{{ $reserva->motivo }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" class="d-inline"
                                        onsubmit="return confirm('¿Cancelar esta reserva?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay reservas próximas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection