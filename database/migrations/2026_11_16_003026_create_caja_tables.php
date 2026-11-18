<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Medios de pago (el admin podrá agregar más después)
        Schema::create('medios_pago', function (Blueprint $table) {
            $table->id('id_medio_pago');
            $table->string('nombre', 50)->unique();
            $table->boolean('activo')->default(true);
        });

        DB::table('medios_pago')->insert([
            ['nombre' => 'Efectivo', 'activo' => true],
            ['nombre' => 'Nequi',    'activo' => true],
            ['nombre' => 'Tarjeta',  'activo' => true],
        ]);

        // Cada cobro de una mesa
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->unsignedInteger('numero')->unique();
            $table->unsignedBigInteger('id_mesa');
            $table->unsignedBigInteger('id_usuario');      // quién cobró
            $table->unsignedBigInteger('id_medio_pago');
            $table->decimal('total', 10, 2);
            $table->decimal('monto_recibido', 10, 2);
            $table->decimal('cambio', 10, 2)->default(0);
            $table->dateTime('fecha_hora');
        });

        // Un pedido con id_pago vacío todavía está por cobrar
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pago')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('id_pago');
        });
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('medios_pago');
    }
};