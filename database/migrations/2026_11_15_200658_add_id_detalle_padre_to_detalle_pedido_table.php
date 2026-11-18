<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pedido', function (Blueprint $table) {
            // Si es un adicional, apunta al plato armado al que pertenece
            $table->unsignedBigInteger('id_detalle_padre')->nullable()->after('id_pedido');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedido', function (Blueprint $table) {
            $table->dropColumn('id_detalle_padre');
        });
    }
};