<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada vez que se corrige la base de una caja abierta queda el registro
        Schema::create('correcciones_caja', function (Blueprint $table) {
            $table->id('id_correccion');
            $table->unsignedBigInteger('id_caja');
            $table->unsignedBigInteger('id_usuario');             // quién corrigió
            $table->decimal('base_anterior', 10, 2);
            $table->decimal('base_nueva', 10, 2);
            $table->string('motivo', 255);
            $table->dateTime('fecha_hora');
            $table->index('id_caja');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correcciones_caja');
    }
};
