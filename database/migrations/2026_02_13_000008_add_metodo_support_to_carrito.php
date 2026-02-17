<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar unique constraint existente
        Schema::table('carrito', function (Blueprint $table) {
            $table->dropUnique('carrito_user_id_problema_id_unique');
        });

        // Hacer problema_id nullable via SQL directo
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NULL');

        // Agregar metodo_id y indices
        Schema::table('carrito', function (Blueprint $table) {
            $table->unsignedBigInteger('metodo_id')->nullable()->after('problema_id');
            $table->foreign('metodo_id')->references('id')->on('metodos')->onDelete('cascade');
            $table->index(['user_id', 'problema_id']);
            $table->index(['user_id', 'metodo_id']);
        });
    }

    public function down(): void
    {
        Schema::table('carrito', function (Blueprint $table) {
            $table->dropForeign(['metodo_id']);
            $table->dropIndex(['user_id', 'metodo_id']);
            $table->dropIndex(['user_id', 'problema_id']);
            $table->dropColumn('metodo_id');
        });

        // Restaurar problema_id a NOT NULL
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NOT NULL');

        Schema::table('carrito', function (Blueprint $table) {
            $table->unique(['user_id', 'problema_id']);
        });
    }
};
