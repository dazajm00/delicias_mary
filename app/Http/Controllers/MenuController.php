<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Plato;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /**
     * Pantalla de menú y precios (solo administrador).
     */
        public function index()
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $precioArmado = (float) DB::table('configuracion')
            ->where('clave', 'precio_plato_armado')
            ->value('valor');

        $categorias = Categoria::orderBy('id_categoria')->get();
        $platos = Plato::with('categoria')->orderBy('id_categoria')->orderBy('nombre')->get();

        $insumos = Insumo::whereIn('tipo', ['PRINCIPIO', 'PROTEINA', 'ACOMPANAMIENTO'])->orderBy('nombre')->get();

        $nequi = DB::table('configuracion')->where('clave', 'nequi_numero')->value('valor');

        return view('menu.index', compact('precioArmado', 'categorias', 'platos', 'insumos', 'nequi'));
    }

    /**
     * Cambiar el precio del plato armado (aplica a los pedidos nuevos).
     */
    public function precioArmado(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'precio' => 'required|numeric|min:0|max:1000000',
        ]);

        DB::table('configuracion')->updateOrInsert(
            ['clave' => 'precio_plato_armado'],
            ['valor' => (string) round($request->precio)]
        );

        return redirect()->route('menu.index')->with('success', 'Precio del plato armado actualizado.');
    }

    /**
     * Guardar el número de Nequi del restaurante (se muestra al cobrar con Nequi).
     */
    public function nequi(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'numero' => ['nullable', 'regex:/^3\d{9}$/'],
        ], [
            'numero.regex' => 'El número de Nequi debe tener 10 dígitos y empezar por 3.',
        ]);

        DB::table('configuracion')->updateOrInsert(
            ['clave' => 'nequi_numero'],
            ['valor' => (string) $request->numero]
        );

        return redirect()->route('menu.index')->with('success', 'Número de Nequi actualizado.');
    }
    /**
     * Agregar un producto al menú.
     */
        /**
     * Agregar algo nuevo: un producto del menú o un ingrediente del almuerzo.
     */
    public function store(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $tiposInsumo = ['PRINCIPIO', 'PROTEINA', 'ACOMPANAMIENTO'];

        // Ingrediente del almuerzo (no lleva precio)
        if (in_array($request->categoria, $tiposInsumo, true)) {
            $request->validate([
                'nombre' => 'required|max:100|unique:insumos,nombre',
            ]);

            Insumo::create([
                'nombre'     => $request->nombre,
                'tipo'       => $request->categoria,
                'disponible' => true,
                'activo'     => true,
            ]);

            return redirect(route('menu.index') . '#ingredientes')->with('success', 'Ingrediente agregado.');
        }

        // Producto del menú
        $request->validate([
            'nombre'    => 'required|max:100|unique:platos,nombre',
            'categoria' => 'required|exists:categorias,id_categoria',
            'precio'    => 'required|numeric|min:0|max:1000000',
        ]);

        Plato::create([
            'id_categoria' => $request->categoria,
            'nombre'       => $request->nombre,
            'precio'       => round($request->precio),
            'disponible'   => true,
            'activo'       => true,
        ]);

        return redirect()->route('menu.index')->with('success', 'Producto agregado al menú.');
    }

    /**
     * Guardar los cambios de un producto.
     */
    public function update(Request $request, Plato $plato)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'nombre'       => 'required|max:100|unique:platos,nombre,' . $plato->id_plato . ',id_plato',
            'id_categoria' => 'required|exists:categorias,id_categoria',
            'precio'       => 'required|numeric|min:0|max:1000000',
        ]);

        $plato->nombre       = $request->nombre;
        $plato->id_categoria = $request->id_categoria;
        $plato->precio       = round($request->precio);
        $plato->activo       = $request->boolean('activo');
        $plato->disponible   = $request->boolean('disponible');
        $plato->save();

        return redirect()->route('menu.index')->with('success', "«{$plato->nombre}» actualizado.");
    }

    /**
     * Eliminar un producto. Si ya se usó en algún pedido, solo se desactiva
     * para no dañar el historial de ventas.
     */
    public function destroy(Plato $plato)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        if (DetallePedido::where('id_plato', $plato->id_plato)->exists()) {
            $plato->activo = false;
            $plato->save();

            return redirect()->route('menu.index')
                ->with('success', "«{$plato->nombre}» ya aparece en pedidos, por eso quedó desactivado en lugar de borrarse.");
        }

        $plato->delete();

        return redirect()->route('menu.index')->with('success', 'Producto eliminado.');
    }
}