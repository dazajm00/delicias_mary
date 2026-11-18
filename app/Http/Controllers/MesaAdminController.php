<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MesaAdminController extends Controller
{
    // Si el administrador aún no ha definido cuántas mesas caben en el local
    private const MAXIMO_POR_DEFECTO = 20;

    /**
     * Cuántas mesas caben en el local (se guarda en la tabla configuracion).
     */
    private function maximo(): int
    {
        $valor = DB::table('configuracion')->where('clave', 'mesas_maximas')->value('valor');

        return $valor ? (int) $valor : self::MAXIMO_POR_DEFECTO;
    }

    /**
     * El número libre más bajo entre 1 y el máximo (rellena los huecos, ej. si falta la 9).
     * Devuelve null si todos los números ya están usados.
     */
    private function siguienteNumero(): ?int
    {
        $usados = Mesa::pluck('numero')->all();

        for ($numero = 1; $numero <= $this->maximo(); $numero++) {
            if (! in_array($numero, $usados)) {
                return $numero;
            }
        }

        return null;
    }

    public function index()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $mesas      = Mesa::orderBy('numero')->get();
        $maximo     = $this->maximo();
        $siguiente  = $this->siguienteNumero();

        return view('mesas.admin', compact('mesas', 'maximo', 'siguiente'));
    }

    /**
     * Agregar una mesa. El número lo asigna el sistema: el primero libre dentro del máximo del local.
     */
    public function store(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'capacidad' => 'required|integer|min:1|max:20',
        ]);

        $numero = $this->siguienteNumero();

        if ($numero === null) {
            return redirect()->route('mesas.admin.index')
                ->with('error', "Ya están usados todos los números del 1 al {$this->maximo()}. Activa una mesa desactivada o aumenta el número de mesas del local.");
        }

        Mesa::create([
            'numero'    => $numero,
            'capacidad' => $request->capacidad,
            'estado'    => 'DISPONIBLE',
            'activa'    => true,
        ]);

        return redirect()->route('mesas.admin.index')->with('success', "Mesa {$numero} agregada.");
    }

    /**
     * Cambiar cuántas mesas caben en el local.
     */
    public function limite(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'maximo' => 'required|integer|min:1|max:100',
        ], [
            'maximo.max' => 'El local puede tener como máximo 100 mesas.',
        ]);

        $mayorEnUso = (int) Mesa::where('activa', true)->max('numero');

        if ($request->maximo < $mayorEnUso) {
            return redirect()->route('mesas.admin.index')
                ->with('error', "No puedes bajar el límite a {$request->maximo}: la mesa {$mayorEnUso} está activa. Quítala o cámbiale el número primero.");
        }

        DB::table('configuracion')->updateOrInsert(
            ['clave' => 'mesas_maximas'],
            ['valor' => (string) $request->maximo]
        );

        return redirect()->route('mesas.admin.index')->with('success', "El local ahora tiene capacidad para {$request->maximo} mesas.");
    }

    public function update(Request $request, Mesa $mesa)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $maximo = $this->maximo();

        $request->validate([
            'numero'    => 'required|integer|min:1|max:' . $maximo . '|unique:mesas,numero,' . $mesa->id_mesa . ',id_mesa',
            'capacidad' => 'required|integer|min:1|max:20',
        ], [
            'numero.unique' => 'Ya existe una mesa con ese número.',
            'numero.max'    => "El local tiene {$maximo} mesas: el número debe estar entre 1 y {$maximo}.",
        ]);

        $mesa->numero    = $request->numero;
        $mesa->capacidad = $request->capacidad;
        $mesa->save();

        return redirect()->route('mesas.admin.index')->with('success', "Mesa {$mesa->numero} actualizada.");
    }

    /**
     * Quitar una mesa. Sin historial se borra; con historial se desactiva
     * para no perder pedidos ni facturas.
     */
    public function destroy(Mesa $mesa)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if ($mesa->estado !== 'DISPONIBLE' || $mesa->pedidosPorCobrar()->exists()) {
            return redirect()->route('mesas.admin.index')
                ->with('error', "La mesa {$mesa->numero} está en uso (ocupada, reservada o con consumo por cobrar). Libérala o cóbrala primero.");
        }

        $tieneReservas  = Schema::hasTable('reservas');
        $reservaFutura  = $tieneReservas && DB::table('reservas')
            ->where('id_mesa', $mesa->id_mesa)
            ->where('estado', 'ACTIVA')
            ->whereDate('fecha', '>=', today())
            ->exists();

        if ($reservaFutura) {
            return redirect()->route('mesas.admin.index')
                ->with('error', "La mesa {$mesa->numero} tiene reservas próximas. Cancélalas primero.");
        }

        $conHistorial = $mesa->pedidos()->exists()
            || DB::table('pagos')->where('id_mesa', $mesa->id_mesa)->exists()
            || ($tieneReservas && DB::table('reservas')->where('id_mesa', $mesa->id_mesa)->exists());

        if ($conHistorial) {
            $mesa->activa = false;
            $mesa->save();

            return redirect()->route('mesas.admin.index')
                ->with('success', "La mesa {$mesa->numero} ya tiene historial, por eso quedó desactivada en lugar de borrarse.");
        }

        $mesa->delete();

        return redirect()->route('mesas.admin.index')->with('success', "Mesa {$mesa->numero} eliminada.");
    }

    public function activar(Mesa $mesa)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if ($mesa->numero > $this->maximo()) {
            return redirect()->route('mesas.admin.index')
                ->with('error', "La mesa {$mesa->numero} está por fuera de las {$this->maximo()} mesas del local. Cámbiale el número o aumenta el límite.");
        }

        $mesa->activa = true;
        $mesa->save();

        return redirect()->route('mesas.admin.index')->with('success', "Mesa {$mesa->numero} activada.");
    }
}