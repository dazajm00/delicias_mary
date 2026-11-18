<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CocinaController extends Controller
{
    // Siguiente estado que puede poner el cocinero
    private const SIGUIENTE = [
        'PENDIENTE'      => 'EN_PREPARACION',
        'EN_PREPARACION' => 'LISTO',
    ];

    /**
     * Pedidos de la cocina, del más viejo al más nuevo.
     */
    public function index()
    {
        if (session('rol') !== 'COCINERO') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $pedidos = Pedido::with(['mesa', 'usuario', 'detalles.plato', 'detalles.insumos', 'detalles.adicionales.plato'])
            ->whereIn('estado', ['PENDIENTE', 'EN_PREPARACION', 'LISTO'])
            ->whereNull('id_pago')
            ->orderBy('fecha_hora')
            ->get();

        return view('cocina.index', compact('pedidos'));
    }

    /**
     * Pasar un pedido al siguiente estado (Empezar o Marcar listo).
     */
    public function avanzar(Request $request, Pedido $pedido)
    {
        if (session('rol') !== 'COCINERO') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $desde = $request->input('desde');

        $avanzo = DB::transaction(function () use ($pedido, $desde) {
            $actual = Pedido::where('id_pedido', $pedido->id_pedido)->lockForUpdate()->first();

            // Si el pedido cambió mientras la pantalla estaba abierta (cancelado, o ya avanzado), no se toca
            if ($actual->estado !== $desde || ! isset(self::SIGUIENTE[$actual->estado])) {
                return false;
            }

            $actual->estado = self::SIGUIENTE[$actual->estado];
            $actual->save();

            return true;
        });

        if (! $avanzo) {
            return redirect()->route('cocina.index')
                ->with('error', "El pedido N.º {$pedido->numero} cambió mientras tenías la pantalla abierta. Ya se actualizó.");
        }

        return redirect()->route('cocina.index');
    }
}