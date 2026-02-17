<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar TODAS las FKs de la tabla (el unique index puede respaldar varias)
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'carrito'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE carrito DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 2. Eliminar unique constraint existente
        DB::statement("ALTER TABLE carrito DROP INDEX `carrito_user_id_problema_id_unique`");

        // 3. Hacer problema_id nullable
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NULL');

        // 4. Agregar metodo_id
        Schema::table('carrito', function (Blueprint $table) {
            $table->unsignedBigInteger('metodo_id')->nullable()->after('problema_id');
        });

        // 5. Re-agregar FKs e indices
        Schema::table('carrito', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('problema_id')->references('id')->on('pim_problems')->onDelete('cascade');
            $table->foreign('metodo_id')->references('id')->on('metodos')->onDelete('cascade');
            $table->index(['user_id', 'problema_id']);
            $table->index(['user_id', 'metodo_id']);
        });
    }

    public function down(): void
    {
        Schema::table('carrito', function (Blueprint $table) {
            $table->dropForeign(['metodo_id']);
            $table->dropForeign(['problema_id']);
            $table->dropIndex(['user_id', 'metodo_id']);
            $table->dropIndex(['user_id', 'problema_id']);
            $table->dropColumn('metodo_id');
        });

        // Eliminar filas sin problema_id antes de restaurar NOT NULL
        DB::table('carrito')->whereNull('problema_id')->delete();

        // Restaurar problema_id a NOT NULL
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NOT NULL');

        Schema::table('carrito', function (Blueprint $table) {
            $table->unique(['user_id', 'problema_id']);
            $table->foreign('problema_id')->references('id')->on('pim_problems')->onDelete('cascade');
        });
    }
};
