<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Insumo;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Plato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    /**
     * Formulario de la comanda de una mesa (CU-07).
     */
    public function create(Mesa $mesa)
    {
        if (session('rol') !== 'MESERO') {
            return redirect()->route('mesas.index')->with('error', 'Solo el mesero puede registrar comandas.');
        }

        return view('pedidos.create', [
            'mesa'    => $mesa,
            'reserva' => $mesa->reservasVigentes()->orderBy('hora')->first(),
        ] + $this->datosFormulario());
    }

    /**
     * Guardar la comanda y enviarla a cocina (RF-03, RF-07).
     */
    public function store(Request $request, Mesa $mesa)
    {
        if (session('rol') !== 'MESERO') {
            return redirect()->route('mesas.index')->with('error', 'Solo el mesero puede registrar comandas.');
        }

        [$platosArmados, $productos] = $this->leerComanda($request);

        // Mesa reservada: confirmar que llegó el cliente
        if ($mesa->estado === 'RESERVADA' && ! $request->has('confirmar_reserva')) {
            return back()->withErrors(['platos' => 'La mesa está reservada. Marca la casilla para confirmar que el cliente ya llegó.'])->withInput();
        }

        // Guardar todo junto: si algo falla, no queda nada a medias (RNF-04)
        DB::transaction(function () use ($mesa, $platosArmados, $productos) {
            $pedido = Pedido::create([
                'numero'     => (Pedido::max('numero') ?? 0) + 1,
                'id_mesa'    => $mesa->id_mesa,
                'id_usuario' => session('id_usuario'),
                'estado'     => 'PENDIENTE',
                'fecha_hora' => now(),
            ]);

            $this->guardarLineas($pedido, $platosArmados, $productos);

            // Si la mesa estaba reservada, el cliente ya llegó: la reserva queda cumplida
            if ($mesa->estado === 'RESERVADA') {
                $mesa->reservasVigentes()->update(['estado' => 'CUMPLIDA']);
            }

            $mesa->estado = 'OCUPADA';
            $mesa->save();
        });

        return redirect()->route('pedidos.index', $mesa)->with('success', 'Comanda enviada a cocina.');
    }

    /**
     * Formulario para editar un pedido (solo mesero y solo si está pendiente).
     */
    public function edit(Pedido $pedido)
    {
        if (session('rol') !== 'MESERO') {
            return redirect()->route('mesas.index')->with('error', 'Solo el mesero puede editar comandas.');
        }

        if ($pedido->estado !== 'PENDIENTE') {
            return redirect()->route('pedidos.index', $pedido->id_mesa)
                ->with('error', "El pedido N.º {$pedido->numero} ya no está pendiente y no se puede editar.");
        }

        // Se rellena el formulario con lo que ya tenía el pedido. Si hubo un error
        // de validación al guardar, se respeta lo que el mesero acababa de escribir.
        if (! session()->hasOldInput()) {
            session()->flashInput($this->datosPrevios($pedido));
        }

        return view('pedidos.create', ['mesa' => $pedido->mesa, 'pedido' => $pedido] + $this->datosFormulario());
    }

    /**
     * Guardar los cambios de un pedido pendiente.
     */
    public function update(Request $request, Pedido $pedido)
    {
        if (session('rol') !== 'MESERO') {
            return redirect()->route('mesas.index')->with('error', 'Solo el mesero puede editar comandas.');
        }

        if ($pedido->estado !== 'PENDIENTE') {
            return redirect()->route('pedidos.index', $pedido->id_mesa)
                ->with('error', "El pedido N.º {$pedido->numero} ya no está pendiente y no se puede editar.");
        }

        [$platosArmados, $productos] = $this->leerComanda($request);

        $editado = DB::transaction(function () use ($pedido, $platosArmados, $productos) {
            // Se vuelve a revisar el estado con el registro bloqueado, por si cocina lo tomó justo ahora
            $actual = Pedido::where('id_pedido', $pedido->id_pedido)->lockForUpdate()->first();
            if ($actual->estado !== 'PENDIENTE') {
                return false;
            }

            // Se borran las líneas viejas y se crean las nuevas
            $ids = DetallePedido::where('id_pedido', $pedido->id_pedido)->pluck('id_detalle_pedido');
            DB::table('detalle_pedido_insumo')->whereIn('id_detalle_pedido', $ids)->delete();
            DetallePedido::where('id_pedido', $pedido->id_pedido)->delete();

            $this->guardarLineas($actual, $platosArmados, $productos);

            return true;
        });

        if (! $editado) {
            return redirect()->route('pedidos.index', $pedido->id_mesa)
                ->with('error', "El pedido N.º {$pedido->numero} ya pasó a cocina y no se puede editar.");
        }

        return redirect()->route('pedidos.index', $pedido->id_mesa)
            ->with('success', "Pedido N.º {$pedido->numero} actualizado.");
    }

    /**
     * Pedidos de una mesa (CU-09, CU-10). Mesero y administrador.
     */
    public function index(Mesa $mesa)
    {
        if (session('rol') !== 'MESERO' && session('rol') !== 'ADMINISTRADOR') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $pedidos = Pedido::with(['usuario', 'detalles.plato', 'detalles.insumos', 'detalles.adicionales.plato'])
            ->where('id_mesa', $mesa->id_mesa)
            ->whereNull('id_pago')
            ->when(! request('cancelados'), fn ($consulta) => $consulta->where('estado', '!=', 'CANCELADO'))
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return view('pedidos.index', compact('mesa', 'pedidos'));
    }

        /**
     * Marcar un pedido listo como entregado a la mesa (solo mesero).
     */
    public function entregar(Pedido $pedido)
    {
        if (session('rol') !== 'MESERO') {
            return redirect()->route('inicio')->with('error', 'Solo el mesero marca los pedidos como entregados.');
        }

        $entregado = DB::transaction(function () use ($pedido) {
            $actual = Pedido::where('id_pedido', $pedido->id_pedido)->lockForUpdate()->first();

            if ($actual->estado !== 'LISTO') {
                return false;
            }

            $actual->estado = 'ENTREGADO';
            $actual->save();

            return true;
        });

        if (! $entregado) {
            return redirect()->route('pedidos.index', $pedido->id_mesa)
                ->with('error', "El pedido N.º {$pedido->numero} todavía no está listo.");
        }

        return redirect()->route('pedidos.index', $pedido->id_mesa)
            ->with('success', "Pedido N.º {$pedido->numero} entregado.");
    }

    /**
     * Cancelar un pedido (RF-12). Solo si sigue pendiente.
     */
    public function cancelar(Pedido $pedido)
    {
        if (session('rol') !== 'MESERO' && session('rol') !== 'ADMINISTRADOR') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        if ($pedido->estado !== 'PENDIENTE') {
            $estados = ['EN_PREPARACION' => 'en preparación', 'LISTO' => 'listo', 'ENTREGADO' => 'entregado', 'CANCELADO' => 'cancelado'];
            return redirect()->route('pedidos.index', $pedido->id_mesa)
                ->with('error', "El pedido N.º {$pedido->numero} ya está {$estados[$pedido->estado]} y no se puede cancelar.");
        }

        // El pedido no se borra: queda como CANCELADO en el historial
        $pedido->estado = 'CANCELADO';
        $pedido->save();
        DetallePedido::where('id_pedido', $pedido->id_pedido)->update(['cancelado' => true]);

        // Si la mesa ya no tiene pedidos sin entregar, vuelve a disponible (regla R4)
        $mesa = $pedido->mesa;
        if (! $mesa->pedidosPorCobrar()->exists()) {
            $mesa->estado = 'DISPONIBLE';
            $mesa->save();
        }

        return redirect()->route('pedidos.index', $mesa->id_mesa)
            ->with('success', "Pedido N.º {$pedido->numero} cancelado.");
    }

    // ------------------------------------------------------------------
    // Funciones de apoyo (las usan store, update y edit)
    // ------------------------------------------------------------------

    /**
     * Datos que necesita el formulario de la comanda (crear y editar).
     */
    private function datosFormulario(): array
    {
        $principios      = Insumo::where('tipo', 'PRINCIPIO')->where('activo', true)->where('disponible', true)->orderBy('nombre')->get();
        $proteinas       = Insumo::where('tipo', 'PROTEINA')->where('activo', true)->where('disponible', true)->orderBy('nombre')->get();
        $acompanamientos = Insumo::where('tipo', 'ACOMPANAMIENTO')->where('activo', true)->where('disponible', true)->orderBy('nombre')->get();

        $categorias = Categoria::with(['platos' => function ($consulta) {
            $consulta->where('activo', true)->where('disponible', true)->orderBy('nombre');
        }])->orderBy('id_categoria')->get();

        $precioArmado = (float) DB::table('configuracion')
            ->where('clave', 'precio_plato_armado')
            ->value('valor');

        // Los adicionales se piden dentro de cada plato, no en el menú de la derecha
        $adicionales = $categorias->first(fn ($c) => strtolower($c->nombre) === 'adicionales')?->platos ?? collect();
        $categorias  = $categorias->reject(fn ($c) => strtolower($c->nombre) === 'adicionales');

        return compact('principios', 'proteinas', 'acompanamientos', 'categorias', 'precioArmado', 'adicionales');
    }

    /**
     * Convierte un pedido guardado en el mismo formato que usa el formulario.
     */
    private function datosPrevios(Pedido $pedido): array
    {
        $pedido->load(['detalles.insumos', 'detalles.adicionales']);

        $platos = [];
        $numero = 1;

        foreach ($pedido->detalles->where('tipo', 'ARMADO')->whereNull('id_detalle_padre') as $detalle) {
            $platos[$numero] = [
                'principio'       => optional($detalle->insumos->firstWhere('tipo', 'PRINCIPIO'))->id_insumo,
                'proteina'        => optional($detalle->insumos->firstWhere('tipo', 'PROTEINA'))->id_insumo,
                'acompanamientos' => $detalle->insumos->where('tipo', 'ACOMPANAMIENTO')->pluck('id_insumo')->all(),
                'observaciones'   => $detalle->observaciones,
                'adicionales'     => $detalle->adicionales->pluck('cantidad', 'id_plato')->all(),
            ];
            $numero++;
        }

        $productos = $pedido->detalles
            ->where('tipo', 'MENU')
            ->whereNull('id_detalle_padre')
            ->pluck('cantidad', 'id_plato')
            ->all();

        return ['platos' => $platos, 'productos' => $productos];
    }

    /**
     * Valida el formulario y devuelve [platos armados, productos del menú].
     * Si algo está mal, vuelve al formulario con el mensaje de error.
     */
    private function leerComanda(Request $request): array
    {
        $request->validate([
            'platos'                      => 'nullable|array',
            'platos.*.principio'          => 'nullable|exists:insumos,id_insumo',
            'platos.*.proteina'           => 'nullable|exists:insumos,id_insumo',
            'platos.*.acompanamientos'    => 'nullable|array',
            'platos.*.acompanamientos.*'  => 'exists:insumos,id_insumo',
            'platos.*.observaciones'      => 'nullable|max:255',
            'platos.*.adicionales'        => 'nullable|array',
            'platos.*.adicionales.*'      => 'nullable|integer|min:0|max:20',
            'productos'                   => 'nullable|array',
            'productos.*'                 => 'nullable|integer|min:0|max:20',
        ]);

        // Cada plato armado necesita principio Y proteína
        $platosArmados = [];
        foreach ($request->input('platos', []) as $numero => $plato) {
            $principio = $plato['principio'] ?? null;
            $proteina  = $plato['proteina'] ?? null;

            if (! $principio && ! $proteina) {
                continue; // plato vacío: no se envía
            }

            if (! $principio || ! $proteina) {
                throw ValidationException::withMessages([
                    'platos' => "El plato {$numero} debe tener un principio y una proteína.",
                ]);
            }

            $platosArmados[] = $plato;
        }

        // Productos del menú fijo con cantidad mayor que 0
        $productos = array_filter($request->input('productos', []), fn ($cantidad) => $cantidad > 0);

        if (count($platosArmados) === 0 && count($productos) === 0) {
            throw ValidationException::withMessages([
                'platos' => 'La comanda está vacía. Agrega al menos un plato antes de guardarla.',
            ]);
        }

        return [$platosArmados, $productos];
    }

    /**
     * Crea las líneas del pedido (platos armados con sus adicionales y productos del menú).
     * El precio se copia a cada línea en este momento, así un cambio de precio posterior
     * no altera lo que ya se vendió.
     */
    private function guardarLineas(Pedido $pedido, array $platosArmados, array $productos): void
    {
        $precioArmado = (float) DB::table('configuracion')
            ->where('clave', 'precio_plato_armado')
            ->value('valor');

        // El arroz (insumo tipo BASE) va incluido en todos los platos
        $arroz = Insumo::where('tipo', 'BASE')->where('disponible', true)->pluck('id_insumo')->toArray();

        foreach ($platosArmados as $plato) {
            $detalle = DetallePedido::create([
                'id_pedido'       => $pedido->id_pedido,
                'tipo'            => 'ARMADO',
                'cantidad'        => 1,
                'precio_unitario' => $precioArmado,
                'observaciones'   => $plato['observaciones'] ?? null,
            ]);

            $insumos = array_merge($arroz, [$plato['principio'], $plato['proteina']], $plato['acompanamientos'] ?? []);
            $detalle->insumos()->attach(array_unique($insumos));

            // Adicionales de este plato (ej. huevo frito), enlazados a él
            foreach (($plato['adicionales'] ?? []) as $idAdicional => $cantidadAd) {
                if ($cantidadAd <= 0) {
                    continue;
                }
                $adicional = Plato::find($idAdicional);
                if (! $adicional) {
                    continue;
                }
                DetallePedido::create([
                    'id_pedido'        => $pedido->id_pedido,
                    'id_detalle_padre' => $detalle->id_detalle_pedido,
                    'tipo'             => 'MENU',
                    'id_plato'         => $adicional->id_plato,
                    'cantidad'         => $cantidadAd,
                    'precio_unitario'  => $adicional->precio,
                ]);
            }
        }

        foreach ($productos as $idPlato => $cantidad) {
            $producto = Plato::find($idPlato);
            if (! $producto) {
                continue;
            }
            DetallePedido::create([
                'id_pedido'       => $pedido->id_pedido,
                'tipo'            => 'MENU',
                'id_plato'        => $producto->id_plato,
                'cantidad'        => $cantidad,
                'precio_unitario' => $producto->precio,
            ]);
        }
    }
}