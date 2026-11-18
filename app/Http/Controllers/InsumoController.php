<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsumoController extends Controller
{
    // El arroz (BASE) no se administra aquí: va incluido en todos los platos
    private const TIPOS = ['PRINCIPIO', 'PROTEINA', 'ACOMPANAMIENTO'];

    public function store(Request $request)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);

        $request->validate([
            'nombre' => 'required|max:100|unique:insumos,nombre',
            'tipo'   => 'required|in:' . implode(',', self::TIPOS),
        ]);

        Insumo::create([
            'nombre'     => $request->nombre,
            'tipo'       => $request->tipo,
            'disponible' => true,
            'activo'     => true,
        ]);

        return redirect()->route('menu.index')->with('success', 'Ingrediente agregado.');
    }

    public function update(Request $request, Insumo $insumo)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);
        abort_if($insumo->tipo === 'BASE', 403);

        $request->validate([
            'nombre' => 'required|max:100|unique:insumos,nombre,' . $insumo->id_insumo . ',id_insumo',
        ]);

        $insumo->nombre     = $request->nombre;
        $insumo->activo     = $request->boolean('activo');
        $insumo->disponible = $request->boolean('disponible');
        $insumo->save();

        return redirect()->route('menu.index')->with('success', "«{$insumo->nombre}» actualizado.");
    }

    /**
     * Eliminar un ingrediente. Si ya se usó en algún pedido, solo se desactiva
     * para no dañar el historial.
     */
    public function destroy(Insumo $insumo)
    {
        abort_unless(session('rol') === 'ADMINISTRADOR', 403);
        abort_if($insumo->tipo === 'BASE', 403);

        $usado = DB::table('detalle_pedido_insumo')->where('id_insumo', $insumo->id_insumo)->exists();

        if ($usado) {
            $insumo->activo = false;
            $insumo->save();

            return redirect()->route('menu.index')
                ->with('success', "«{$insumo->nombre}» ya aparece en pedidos, por eso quedó desactivado en lugar de borrarse.");
        }

        $insumo->delete();

        return redirect()->route('menu.index')->with('success', 'Ingrediente eliminado.');
    }
}