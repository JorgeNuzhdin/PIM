<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar TODAS las FKs de la tabla
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

        // 2. Eliminar TODOS los indices no-primarios (evita conflictos con indices huerfanos)
        $indices = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'carrito'
              AND INDEX_NAME != 'PRIMARY'
        ");
        foreach ($indices as $idx) {
            DB::statement("ALTER TABLE carrito DROP INDEX `{$idx->INDEX_NAME}`");
        }

        // 3. Hacer problema_id nullable
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NULL');

        // 4. Agregar metodo_id si no existe (la migracion pudo haber fallado parcialmente)
        $metodoCol = DB::selectOne("
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'carrito'
              AND COLUMN_NAME = 'metodo_id'
        ");
        if (!$metodoCol) {
            DB::statement('ALTER TABLE carrito ADD COLUMN metodo_id BIGINT UNSIGNED NULL AFTER problema_id');
        }

        // 5. Crear indices frescos
        DB::statement('CREATE INDEX carrito_user_id_problema_id_index ON carrito (user_id, problema_id)');
        DB::statement('CREATE INDEX carrito_user_id_metodo_id_index ON carrito (user_id, metodo_id)');

        // 6. Re-agregar FKs
        DB::statement('ALTER TABLE carrito ADD CONSTRAINT carrito_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE carrito ADD CONSTRAINT carrito_problema_id_foreign FOREIGN KEY (problema_id) REFERENCES pim_problems(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE carrito ADD CONSTRAINT carrito_metodo_id_foreign FOREIGN KEY (metodo_id) REFERENCES metodos(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Eliminar FKs e indices
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

        $indices = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'carrito'
              AND INDEX_NAME != 'PRIMARY'
        ");
        foreach ($indices as $idx) {
            DB::statement("ALTER TABLE carrito DROP INDEX `{$idx->INDEX_NAME}`");
        }

        // Quitar columna metodo_id
        Schema::table('carrito', function (Blueprint $table) {
            $table->dropColumn('metodo_id');
        });

        // Eliminar filas sin problema_id antes de restaurar NOT NULL
        DB::table('carrito')->whereNull('problema_id')->delete();

        // Restaurar problema_id a NOT NULL
        DB::statement('ALTER TABLE carrito MODIFY problema_id BIGINT UNSIGNED NOT NULL');

        // Restaurar indices y FKs originales
        DB::statement('CREATE UNIQUE INDEX carrito_user_id_problema_id_unique ON carrito (user_id, problema_id)');
        DB::statement('ALTER TABLE carrito ADD CONSTRAINT carrito_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE carrito ADD CONSTRAINT carrito_problema_id_foreign FOREIGN KEY (problema_id) REFERENCES pim_problems(id) ON DELETE CASCADE');
    }
};
