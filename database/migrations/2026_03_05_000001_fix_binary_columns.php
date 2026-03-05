<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // pim_sheets.preambles: text → longtext
        DB::statement('ALTER TABLE `pim_sheets` MODIFY `preambles` LONGTEXT');

        // pim_figures_in_intros.figure: blob → longblob
        DB::statement('ALTER TABLE `pim_figures_in_intros` MODIFY `figure` LONGBLOB');

        // metodo_figures.figure: longtext → longblob (datos binarios, no texto)
        DB::statement('ALTER TABLE `metodo_figures` MODIFY `figure` LONGBLOB');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `pim_sheets` MODIFY `preambles` TEXT');
        DB::statement('ALTER TABLE `pim_figures_in_intros` MODIFY `figure` BLOB');
        DB::statement('ALTER TABLE `metodo_figures` MODIFY `figure` LONGTEXT');
    }
};
