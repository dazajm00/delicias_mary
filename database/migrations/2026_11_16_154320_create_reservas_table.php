<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id('id_reserva');
            $table->unsignedBigInteger('id_mesa');
            $table->unsignedBigInteger('id_usuario');      // quién la registró
            $table->string('nombre_cliente', 100);
            $table->unsignedTinyInteger('personas');
            $table->date('fecha');
            $table->time('hora');
            $table->string('motivo', 255);
            $table->string('estado', 20)->default('ACTIVA'); // ACTIVA, CUMPLIDA o CANCELADA
            $table->dateTime('creada_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};