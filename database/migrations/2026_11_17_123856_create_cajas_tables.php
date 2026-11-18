<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada turno de caja: se abre con una base y se cierra con el arqueo
        Schema::create('cajas', function (Blueprint $table) {
            $table->id('id_caja');
            $table->unsignedBigInteger('id_usuario_apertura');
            $table->decimal('base', 10, 2);                       // efectivo con el que empieza el cajón
            $table->dateTime('abierta_en');
            $table->unsignedBigInteger('id_usuario_cierre')->nullable();
            $table->dateTime('cerrada_en')->nullable();           // vacía = caja abierta
            // Resumen guardado en el momento del cierre
            $table->decimal('ventas_efectivo', 10, 2)->nullable();
            $table->decimal('ventas_otros', 10, 2)->nullable();   // Nequi, tarjeta u otros medios
            $table->decimal('entradas', 10, 2)->nullable();
            $table->decimal('salidas', 10, 2)->nullable();
            $table->decimal('esperado', 10, 2)->nullable();       // lo que debería haber en el cajón
            $table->decimal('contado', 10, 2)->nullable();        // lo que contó el administrador
            $table->decimal('diferencia', 10, 2)->nullable();     // contado - esperado (negativo = faltante)
            $table->string('nota', 255)->nullable();
        });

        // Entradas y salidas de efectivo que no son ventas (compras, cambio, etc.)
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('id_caja');
            $table->unsignedBigInteger('id_usuario');
            $table->string('tipo', 10);                           // ENTRADA o SALIDA
            $table->decimal('valor', 10, 2);
            $table->string('motivo', 255);
            $table->dateTime('fecha_hora');
            $table->index('id_caja');
        });

        // Cada cobro queda en la caja que estaba abierta
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_caja')->nullable();
            $table->index('id_caja');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['id_caja']);
            $table->dropColumn('id_caja');
        });
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
    }
};