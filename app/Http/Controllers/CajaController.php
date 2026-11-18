<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CorreccionCaja;
use App\Models\MedioPago;
use App\Models\Mesa;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    // Base con la que se propone abrir la caja (se puede cambiar al abrir)
    private const BASE_SUGERIDA = 100000;

    /**
     * Pantalla de caja: estado de la caja, mesas por cobrar, ventas y movimientos del turno.
     */
    public function index()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $mesas = Mesa::where('activa', true)->orderBy('numero')->get()->each(function ($mesa) {
            $pedidos = $mesa->pedidosPorCobrar()->with('detalles')->get();
            $mesa->por_cobrar       = $pedidos->count();
            $mesa->total_por_cobrar = $pedidos->sum(fn ($p) => $p->total());
        });
        $porCobrar = $mesas->filter(fn ($mesa) => $mesa->por_cobrar > 0);

        $caja         = Caja::abierta();
        $ultimoCierre = Caja::whereNotNull('cerrada_en')->latest('cerrada_en')->first();
        $baseSugerida = self::BASE_SUGERIDA;

        // Ventas y movimientos de la caja abierta
        $pagos       = collect();
        $movimientos = collect();
        $correcciones = collect();
        $porMedio    = collect();
        $totalVentas = 0;
        $efectivo    = null;

        if ($caja) {
            $pagos       = $caja->pagos()->with(['mesa', 'medio'])->orderByDesc('fecha_hora')->get();
            $movimientos = $caja->movimientos()->with('usuario')->orderByDesc('fecha_hora')->get();
            $correcciones = $caja->correcciones()->with('usuario')->orderByDesc('fecha_hora')->get();
            $porMedio    = $pagos->groupBy(fn ($p) => $p->medio->nombre ?? 'Otro')->map->sum('total');
            $totalVentas = $pagos->sum('total');
            $efectivo    = [
                'base'     => (float) $caja->base,
                'ventas'   => $caja->ventasEfectivo(),
                'entradas' => $caja->totalEntradas(),
                'salidas'  => $caja->totalSalidas(),
                'esperado' => $caja->efectivoEsperado(),
            ];
        }

        return view('caja.index', compact(
            'porCobrar', 'caja', 'ultimoCierre', 'baseSugerida',
            'pagos', 'movimientos', 'correcciones', 'porMedio', 'totalVentas', 'efectivo'
        ));
    }

    /**
     * Abrir la caja con una base de efectivo.
     */
    public function abrir(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'base' => 'required|numeric|min:0|max:10000000',
        ], [
            'base.required' => 'Escribe con cuánto efectivo empieza la caja.',
        ]);

        $abrio = DB::transaction(function () use ($request) {
            // Solo puede haber una caja abierta
            if (Caja::whereNull('cerrada_en')->lockForUpdate()->exists()) {
                return false;
            }

            Caja::create([
                'id_usuario_apertura' => session('id_usuario'),
                'base'                => round($request->base),
                'abierta_en'          => now(),
            ]);

            return true;
        });

        if (! $abrio) {
            return redirect()->route('caja.index')->with('error', 'Ya hay una caja abierta.');
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja abierta con una base de $' . number_format(round($request->base), 0, ',', '.') . '.');
    }

    /**
     * Cancelar una apertura hecha por error: solo si la caja no tiene cobros ni movimientos.
     */
    public function cancelar()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $error = DB::transaction(function () {
            $caja = Caja::whereNull('cerrada_en')->lockForUpdate()->first();
            if (! $caja) {
                return 'No hay una caja abierta para cancelar.';
            }
            if (! $caja->sePuedeCancelar()) {
                return 'No se puede cancelar: la caja ya tiene cobros o movimientos. Si la base está mal, usa «Corregir base».';
            }

            $caja->correcciones()->delete();
            $caja->delete();

            return null;
        });

        if ($error) {
            return redirect()->route('caja.index')->with('error', $error);
        }

        return redirect()->route('caja.index')->with('success', 'Se canceló la apertura de la caja.');
    }

    /**
     * Corregir la base de la caja abierta. Queda registrado quién, cuándo, de cuánto a cuánto y por qué.
     */
    public function corregirBase(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'base_nueva'        => 'required|numeric|min:0|max:10000000',
            'motivo_correccion' => 'required|max:255',
        ], [
            'base_nueva.required'        => 'Escribe la base correcta.',
            'motivo_correccion.required' => 'Escribe por qué corriges la base.',
        ]);

        $resultado = DB::transaction(function () use ($request) {
            $caja = Caja::whereNull('cerrada_en')->lockForUpdate()->first();
            if (! $caja) {
                return 'No hay una caja abierta.';
            }

            $anterior = (float) $caja->base;
            $nueva    = round($request->base_nueva);

            if ($nueva == $anterior) {
                return 'La base nueva es igual a la actual.';
            }

            // Si ya salió efectivo, la base no puede quedar tan baja que el cajón dé negativo
            $esperadoNuevo = $caja->efectivoEsperado() - $anterior + $nueva;
            if ($esperadoNuevo < 0) {
                return 'Con esa base el efectivo del cajón quedaría en negativo. Revisa el valor.';
            }

            CorreccionCaja::create([
                'id_caja'       => $caja->id_caja,
                'id_usuario'    => session('id_usuario'),
                'base_anterior' => $anterior,
                'base_nueva'    => $nueva,
                'motivo'        => $request->motivo_correccion,
                'fecha_hora'    => now(),
            ]);

            $caja->update(['base' => $nueva]);

            return null;
        });

        if ($resultado) {
            // Como error del campo, para que el formulario de corrección siga abierto
            return redirect()->route('caja.index')->withErrors(['base_nueva' => $resultado])->withInput();
        }

        return redirect()->route('caja.index')
            ->with('success', 'Base corregida a $' . number_format(round($request->base_nueva), 0, ',', '.') . '.');
    }

    /**
     * Registrar una entrada o salida de efectivo que no es una venta.
     */
    public function movimiento(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'tipo'   => 'required|in:ENTRADA,SALIDA',
            'valor'  => 'required|numeric|min:1|max:10000000',
            'motivo' => 'required|max:255',
        ], [
            'motivo.required' => 'Escribe el motivo del movimiento.',
            'valor.min'       => 'El valor debe ser mayor que $0.',
        ]);

        $resultado = DB::transaction(function () use ($request) {
            $caja = Caja::whereNull('cerrada_en')->lockForUpdate()->first();
            if (! $caja) {
                return 'La caja está cerrada. Ábrela antes de registrar movimientos.';
            }

            $valor = round($request->valor);

            // No puede salir más efectivo del que hay en el cajón
            if ($request->tipo === 'SALIDA' && $valor > $caja->efectivoEsperado()) {
                return 'No hay suficiente efectivo en caja: hay $' . number_format($caja->efectivoEsperado(), 0, ',', '.') . '.';
            }

            MovimientoCaja::create([
                'id_caja'    => $caja->id_caja,
                'id_usuario' => session('id_usuario'),
                'tipo'       => $request->tipo,
                'valor'      => $valor,
                'motivo'     => $request->motivo,
                'fecha_hora' => now(),
            ]);

            return null;
        });

        if ($resultado) {
            return redirect()->route('caja.index')->with('error', $resultado)->withInput();
        }

        return redirect()->route('caja.index')
            ->with('success', ($request->tipo === 'ENTRADA' ? 'Entrada' : 'Salida') . ' de efectivo registrada.');
    }

    /**
     * Pantalla para cerrar la caja: muestra cuánto debería haber y pide lo contado.
     */
    public function cierreForm()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $caja = Caja::abierta();
        if (! $caja) {
            return redirect()->route('caja.index')->with('error', 'No hay una caja abierta para cerrar.');
        }

        $porMedio = $caja->ventasPorMedio();
        $mesasPorCobrar = Mesa::where('activa', true)->get()
            ->filter(fn ($mesa) => $mesa->pedidosPorCobrar()->exists())->count();

        return view('caja.cerrar', [
            'caja'           => $caja,
            'correcciones'   => $caja->correcciones()->with('usuario')->orderBy('fecha_hora')->get(),
            'porMedio'       => $porMedio,
            'mesasPorCobrar' => $mesasPorCobrar,
            'efectivo'       => [
                'base'     => (float) $caja->base,
                'ventas'   => $caja->ventasEfectivo(),
                'entradas' => $caja->totalEntradas(),
                'salidas'  => $caja->totalSalidas(),
                'esperado' => $caja->efectivoEsperado(),
            ],
        ]);
    }

    /**
     * Cerrar la caja: guarda el arqueo (esperado, contado y diferencia).
     */
    public function cerrar(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'contado' => 'required|numeric|min:0|max:100000000',
            'nota'    => 'nullable|max:255',
        ], [
            'contado.required' => 'Escribe cuánto efectivo contaste en el cajón.',
        ]);

        $caja = DB::transaction(function () use ($request) {
            $caja = Caja::whereNull('cerrada_en')->lockForUpdate()->first();
            if (! $caja) {
                return null;
            }

            $ventas   = $caja->pagos()->sum('total');
            $efectivo = $caja->ventasEfectivo();
            $esperado = $caja->efectivoEsperado();
            $contado  = round($request->contado);

            $caja->update([
                'id_usuario_cierre' => session('id_usuario'),
                'cerrada_en'        => now(),
                'ventas_efectivo'   => $efectivo,
                'ventas_otros'      => $ventas - $efectivo,
                'entradas'          => $caja->totalEntradas(),
                'salidas'           => $caja->totalSalidas(),
                'esperado'          => $esperado,
                'contado'           => $contado,
                'diferencia'        => $contado - $esperado,
                'nota'              => $request->nota,
            ]);

            return $caja;
        });

        if (! $caja) {
            return redirect()->route('caja.index')->with('error', 'La caja ya estaba cerrada.');
        }

        return redirect()->route('caja.cierre', $caja)->with('success', 'Caja cerrada.');
    }

    /**
     * Historial de cierres de caja.
     */
    public function cierres()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $cajas = Caja::with(['usuarioApertura', 'usuarioCierre'])
            ->whereNotNull('cerrada_en')
            ->orderByDesc('cerrada_en')
            ->paginate(20);

        return view('caja.cierres', compact('cajas'));
    }

    /**
     * Detalle (e impresión) de un cierre de caja.
     */
    public function cierre(Caja $caja)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if ($caja->estaAbierta()) {
            return redirect()->route('caja.index');
        }

        $caja->load(['usuarioApertura', 'usuarioCierre']);
        $porMedio    = $caja->ventasPorMedio();
        $movimientos = $caja->movimientos()->with('usuario')->orderBy('fecha_hora')->get();
        $cobros      = $caja->pagos()->count();
        $correcciones = $caja->correcciones()->with('usuario')->orderBy('fecha_hora')->get();

        return view('caja.cierre', compact('caja', 'porMedio', 'movimientos', 'cobros', 'correcciones'));
    }

    /**
     * Detalle de un turno cerrado: cada cobro, los productos vendidos, las entradas/salidas y las correcciones.
     */
    public function cierreDetalle(Caja $caja)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if ($caja->estaAbierta()) {
            return redirect()->route('caja.index');
        }

        $pagos = $caja->pagos()
            ->with(['mesa', 'medio', 'usuario', 'pedidos.detalles.plato'])
            ->orderBy('fecha_hora')
            ->get();

        // Productos vendidos en el turno, agrupados por nombre
        $productos = $pagos->flatMap(fn ($pago) => $pago->pedidos)
            ->flatMap(fn ($pedido) => $pedido->detalles)
            ->groupBy(function ($detalle) {
                if ($detalle->tipo === 'ARMADO') {
                    return 'Almuerzo';
                }
                $nombre = $detalle->plato->nombre ?? 'Producto';

                return $detalle->id_detalle_padre ? $nombre . ' (adicional)' : $nombre;
            })
            ->map(fn ($lineas, $nombre) => [
                'nombre'   => $nombre,
                'cantidad' => $lineas->sum('cantidad'),
                'total'    => $lineas->sum(fn ($d) => $d->cantidad * $d->precio_unitario),
            ])
            ->sortByDesc('cantidad')
            ->values();

        $movimientos  = $caja->movimientos()->with('usuario')->orderBy('fecha_hora')->get();
        $correcciones = $caja->correcciones()->with('usuario')->orderBy('fecha_hora')->get();

        return view('caja.cierre_detalle', compact('caja', 'pagos', 'productos', 'movimientos', 'correcciones'));
    }

    /**
     * Pantalla de cobro de una mesa.
     */
    public function show(Mesa $mesa)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if (! Caja::abierta()) {
            return redirect()->route('caja.index')->with('error', 'Abre la caja antes de cobrar.');
        }

        $pedidos = $mesa->pedidosPorCobrar()
            ->with(['usuario', 'detalles.plato', 'detalles.insumos', 'detalles.adicionales.plato'])
            ->orderBy('fecha_hora')
            ->get();

        if ($pedidos->isEmpty()) {
            return redirect()->route('caja.index')->with('error', "La mesa {$mesa->numero} no tiene nada por cobrar.");
        }

        $total       = $pedidos->sum(fn ($p) => $p->total());
        $sinEntregar = $pedidos->where('estado', '!=', 'ENTREGADO')->count();
        $medios      = MedioPago::where('activo', true)->orderBy('id_medio_pago')->get();

        $nequi = DB::table('configuracion')->where('clave', 'nequi_numero')->value('valor');

        return view('caja.show', compact('mesa', 'pedidos', 'total', 'sinEntregar', 'medios', 'nequi'));
    }

    /**
     * Registrar el cobro: guarda el pago, marca los pedidos como pagados y libera la mesa.
     * El total se calcula aquí con los precios guardados, nunca viene del navegador.
     */
    public function cobrar(Request $request, Mesa $mesa)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'id_medio_pago'  => 'required|exists:medios_pago,id_medio_pago',
            'monto_recibido' => 'nullable|numeric|min:0|max:100000000',
        ]);

        $medio = MedioPago::where('activo', true)->find($request->id_medio_pago);
        if (! $medio) {
            return back()->withErrors(['id_medio_pago' => 'Elige un medio de pago válido.'])->withInput();
        }

        if (! Caja::abierta()) {
            return redirect()->route('caja.index')->with('error', 'Abre la caja antes de cobrar.');
        }

        $pago = DB::transaction(function () use ($request, $mesa, $medio) {
            // El cobro queda en la caja abierta; se bloquea para que no la cierren al mismo tiempo
            $caja = Caja::whereNull('cerrada_en')->lockForUpdate()->first();
            if (! $caja) {
                throw ValidationException::withMessages([
                    'id_medio_pago' => 'La caja se cerró. Ábrela de nuevo para cobrar.',
                ]);
            }

            $pedidos = $mesa->pedidosPorCobrar()->lockForUpdate()->get();
            if ($pedidos->isEmpty()) {
                return null;
            }

            // No se cobra mientras haya comida en cocina: al cobrar, los pedidos
            // salen de la pantalla de cocina y la mesa queda disponible
            if ($pedidos->contains(fn ($p) => $p->estado !== 'ENTREGADO')) {
                throw ValidationException::withMessages([
                    'id_medio_pago' => 'La mesa tiene pedidos sin entregar. Cóbrala cuando el mesero los entregue.',
                ]);
            }

            $pedidos->load('detalles');
            $total = $pedidos->sum(fn ($p) => $p->total());

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'monto_recibido' => 'El total a cobrar es $0. Revisa los precios de los pedidos.',
                ]);
            }

            if (strtolower($medio->nombre) === 'efectivo') {
                $recibido = (float) $request->monto_recibido;
                if ($recibido < $total) {
                    throw ValidationException::withMessages([
                        'monto_recibido' => 'El monto recibido no alcanza para cubrir el total.',
                    ]);
                }
                $cambio = $recibido - $total;
            } else {
                // Nequi o tarjeta: se cobra el valor exacto
                $recibido = $total;
                $cambio   = 0;
            }

            $pago = Pago::create([
                'numero'         => (Pago::max('numero') ?? 0) + 1,
                'id_mesa'        => $mesa->id_mesa,
                'id_usuario'     => session('id_usuario'),
                'id_medio_pago'  => $medio->id_medio_pago,
                'total'          => $total,
                'monto_recibido' => $recibido,
                'cambio'         => $cambio,
                'fecha_hora'     => now(),
                'id_caja'        => $caja->id_caja,
            ]);

            Pedido::whereIn('id_pedido', $pedidos->pluck('id_pedido'))->update(['id_pago' => $pago->id_pago]);

            $mesa->estado = 'DISPONIBLE';
            $mesa->save();

            return $pago;
        });

        if (! $pago) {
            return redirect()->route('caja.index')->with('error', "La mesa {$mesa->numero} ya no tiene nada por cobrar.");
        }

        return redirect()->route('caja.factura', $pago)
            ->with('success', "Cobro registrado. La mesa {$mesa->numero} quedó disponible.");
    }

    /**
     * Factura de un cobro.
     */
    public function factura(Pago $pago)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $pago->load(['mesa', 'usuario', 'medio', 'pedidos.detalles.plato', 'pedidos.detalles.insumos', 'pedidos.detalles.adicionales.plato']);

        return view('caja.factura', compact('pago'));
    }
}