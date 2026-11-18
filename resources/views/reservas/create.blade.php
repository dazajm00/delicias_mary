@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <h1 class="h3 m-0">Reservar · Mesa {{ $mesa->numero }}
                        <small class="text-muted fs-6">{{ $mesa->capacidad }} puestos</small>
                    </h1>
                    <a href="{{ route('mesas.index') }}">← Volver a mesas</a>
                </div>

                @if ($proximas->count() > 0)
                    <div class="alert alert-light border small">
                        <strong>Reservas de esta mesa:</strong>
                        @foreach ($proximas as $r)
                            <div>{{ $r->fechaTexto() }} · {{ $r->horaTexto() }} · {{ $r->nombre_cliente }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="card">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('reservas.store', $mesa) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="nombre_cliente" class="form-label">Nombre del cliente</label>
                                <input type="text" name="nombre_cliente" id="nombre_cliente" maxlength="100"
                                    value="{{ old('nombre_cliente') }}"
                                    class="form-control @error('nombre_cliente') is-invalid @enderror">
                                @error('nombre_cliente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="fecha" class="form-label">Día</label>
                                    <input type="date" name="fecha" id="fecha" min="{{ today()->format('Y-m-d') }}"
                                        value="{{ old('fecha', today()->format('Y-m-d')) }}"
                                        class="form-control @error('fecha') is-invalid @enderror">
                                    @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="hora" class="form-label">Hora</label>
                                    <input type="time" name="hora" id="hora" value="{{ old('hora') }}"
                                        class="form-control @error('hora') is-invalid @enderror">
                                    @error('hora') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="personas" class="form-label">Personas</label>
                                    <input type="number" name="personas" id="personas" min="1" max="{{ $mesa->capacidad }}"
                                        value="{{ old('personas') }}"
                                        class="form-control @error('personas') is-invalid @enderror">
                                    @error('personas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="motivo" class="form-label">Motivo de la reserva</label>
                                <input type="text" name="motivo" id="motivo" maxlength="255" list="motivos"
                                    value="{{ old('motivo') }}"
                                    class="form-control @error('motivo') is-invalid @enderror">
                                <datalist id="motivos">
                                    <option value="Cumpleaños">
                                    <option value="Reunión familiar">
                                    <option value="Reunión de trabajo">
                                    <option value="Aniversario">
                                    <option value="Celebración">
                                </datalist>
                                @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Elige una sugerencia o escribe el motivo.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-delicias">Guardar reserva</button>
                                <a href="{{ route('mesas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection