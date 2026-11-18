<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    private function sinPermiso(): bool
    {
        return ! in_array(session('rol'), ['MESERO', 'ADMINISTRADOR']);
    }

    /**
     * Próximas reservas.
     */
    public function index()
    {
        if ($this->sinPermiso()) {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $reservas = Reserva::with('mesa')
            ->where('estado', 'ACTIVA')
            ->whereDate('fecha', '>=', today())
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        return view('reservas.index', compact('reservas'));
    }

    /**
     * Formulario para reservar una mesa.
     */
    public function create(Mesa $mesa)
    {
        if ($this->sinPermiso()) {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $proximas = $mesa->reservas()
            ->where('estado', 'ACTIVA')
            ->whereDate('fecha', '>=', today())
            ->orderBy('fecha')->orderBy('hora')
            ->get();

        return view('reservas.create', compact('mesa', 'proximas'));
    }

    /**
     * Guardar la reserva.
     */
    public function store(Request $request, Mesa $mesa)
    {
        if ($this->sinPermiso()) {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $request->validate([
            'nombre_cliente' => 'required|max:100',
            'personas'       => 'required|integer|min:1|max:' . $mesa->capacidad,
            'fecha'          => 'required|date|after_or_equal:today',
            'hora'           => 'required|date_format:H:i',
            'motivo'         => 'required|max:255',
        ], [
            'personas.max' => "La mesa {$mesa->numero} tiene {$mesa->capacidad} puestos.",
        ]);

        $momento = Carbon::parse($request->fecha . ' ' . $request->hora);

        if ($momento->isPast()) {
            throw ValidationException::withMessages(['hora' => 'Esa hora ya pasó. Elige una hora futura.']);
        }

        // Dejar al menos 2 horas entre reservas de la misma mesa
        $choque = $mesa->reservas()
            ->where('estado', 'ACTIVA')
            ->whereDate('fecha', $request->fecha)
            ->get()
            ->first(fn ($otra) => abs(Carbon::parse($request->fecha . ' ' . $otra->hora)->diffInMinutes($momento)) < 120);

        if ($choque) {
            throw ValidationException::withMessages([
                'hora' => 'La mesa ya tiene una reserva a las ' . $choque->horaTexto() . '. Deja al menos 2 horas entre reservas.',
            ]);
        }

        $reserva = Reserva::create([
            'id_mesa'        => $mesa->id_mesa,
            'id_usuario'     => session('id_usuario'),
            'nombre_cliente' => $request->nombre_cliente,
            'personas'       => $request->personas,
            'fecha'          => $request->fecha,
            'hora'           => $request->hora,
            'motivo'         => $request->motivo,
            'estado'         => 'ACTIVA',
            'creada_en'      => now(),
        ]);

        return redirect()->route('reservas.index')
            ->with('success', "Mesa {$mesa->numero} reservada: {$reserva->fechaTexto()} a las {$reserva->horaTexto()}.");
    }

    /**
     * Cancelar una reserva.
     */
    public function cancelar(Reserva $reserva)
    {
        if ($this->sinPermiso()) {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        if ($reserva->estado !== 'ACTIVA') {
            return redirect()->route('reservas.index')->with('error', 'Esa reserva ya no está activa.');
        }

        $reserva->estado = 'CANCELADA';
        $reserva->save();

        // Si la mesa estaba marcada como reservada solo por esta reserva, queda disponible
        $mesa = $reserva->mesa;
        if ($mesa->estado === 'RESERVADA' && ! $mesa->reservasVigentes()->exists()) {
            $mesa->estado = 'DISPONIBLE';
            $mesa->save();
        }

        return redirect()->route('reservas.index')->with('success', 'Reserva cancelada.');
    }
}