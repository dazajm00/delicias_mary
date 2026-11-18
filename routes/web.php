<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\MesaAdminController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\CocinaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Inicio de sesión (RF-01)
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/inicio', [LoginController::class, 'inicio'])->name('inicio');

// Gestión de usuarios (RF-11), solo administrador
Route::resource('usuarios', UsuarioController::class)->except(['show']);
Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');

// Módulo del mesero: mesas (RF-02)
Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
Route::post('/mesas/{mesa}/liberar', [MesaController::class, 'liberar'])->name('mesas.liberar');

// Módulo del mesero: comanda de una mesa (RF-03, RF-07)
Route::get('/mesas/{mesa}/comanda', [PedidoController::class, 'create'])->name('pedidos.create');
Route::post('/mesas/{mesa}/comanda', [PedidoController::class, 'store'])->name('pedidos.store');

// Pedidos de una mesa y cancelación (RF-12), mesero y administrador
Route::get('/mesas/{mesa}/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
Route::post('/pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');
// Editar un pedido mientras siga pendiente (solo mesero)
Route::get('/pedidos/{pedido}/editar', [PedidoController::class, 'edit'])->name('pedidos.edit');
Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('pedidos.update');
// Menú y precios, solo administrador
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::post('/menu/precio-armado', [MenuController::class, 'precioArmado'])->name('menu.precio');
Route::post('/menu/nequi', [MenuController::class, 'nequi'])->name('menu.nequi');
Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
Route::put('/menu/{plato}', [MenuController::class, 'update'])->name('menu.update');
Route::delete('/menu/{plato}', [MenuController::class, 'destroy'])->name('menu.destroy');
// Caja: cobro de mesas y facturas, solo administrador
Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
// Apertura, movimientos de efectivo y cierre (arqueo) de caja, solo administrador
Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
Route::post('/caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
Route::post('/caja/cancelar', [CajaController::class, 'cancelar'])->name('caja.cancelar');
Route::post('/caja/corregir-base', [CajaController::class, 'corregirBase'])->name('caja.corregir');
Route::get('/caja/cerrar', [CajaController::class, 'cierreForm'])->name('caja.cerrar.form');
Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
Route::get('/caja/cierres', [CajaController::class, 'cierres'])->name('caja.cierres');
Route::get('/caja/cierres/{caja}', [CajaController::class, 'cierre'])->name('caja.cierre');
Route::get('/caja/cierres/{caja}/detalle', [CajaController::class, 'cierreDetalle'])->name('caja.cierre.detalle');
Route::get('/caja/factura/{pago}', [CajaController::class, 'factura'])->name('caja.factura');
Route::get('/caja/{mesa}', [CajaController::class, 'show'])->name('caja.show');
Route::post('/caja/{mesa}', [CajaController::class, 'cobrar'])->name('caja.cobrar');
// Ingredientes del almuerzo, solo administrador
Route::post('/insumos', [InsumoController::class, 'store'])->name('insumos.store');
Route::put('/insumos/{insumo}', [InsumoController::class, 'update'])->name('insumos.update');
Route::delete('/insumos/{insumo}', [InsumoController::class, 'destroy'])->name('insumos.destroy');
// Administrar mesas, solo administrador
Route::get('/admin/mesas', [MesaAdminController::class, 'index'])->name('mesas.admin.index');
Route::post('/admin/mesas', [MesaAdminController::class, 'store'])->name('mesas.admin.store');
Route::post('/admin/mesas/limite', [MesaAdminController::class, 'limite'])->name('mesas.admin.limite');
Route::put('/admin/mesas/{mesa}', [MesaAdminController::class, 'update'])->name('mesas.admin.update');
Route::delete('/admin/mesas/{mesa}', [MesaAdminController::class, 'destroy'])->name('mesas.admin.destroy');
Route::post('/admin/mesas/{mesa}/activar', [MesaAdminController::class, 'activar'])->name('mesas.admin.activar');
// Reservas (mesero y administrador)
Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
Route::get('/mesas/{mesa}/reservar', [ReservaController::class, 'create'])->name('reservas.create');
Route::post('/mesas/{mesa}/reservar', [ReservaController::class, 'store'])->name('reservas.store');
Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
// Cocina (cocinero) y entrega (mesero)
Route::get('/cocina', [CocinaController::class, 'index'])->name('cocina.index');
Route::post('/cocina/{pedido}/avanzar', [CocinaController::class, 'avanzar'])->name('cocina.avanzar');
Route::post('/pedidos/{pedido}/entregar', [PedidoController::class, 'entregar'])->name('pedidos.entregar');