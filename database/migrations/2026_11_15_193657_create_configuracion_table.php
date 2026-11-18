<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->string('clave', 50)->primary();
            $table->string('valor', 255);
        });

        // Precio inicial del plato armado (el admin lo podrá cambiar)
        DB::table('configuracion')->insert([
            'clave' => 'precio_plato_armado',
            'valor' => '20000',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};