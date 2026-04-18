<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE hoja_problem DROP FOREIGN KEY hoja_problem_ibfk_2');
        DB::statement('ALTER TABLE hoja_problem ADD CONSTRAINT hoja_problem_problem_id_fk FOREIGN KEY (problem_id) REFERENCES pim_problems (id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE hoja_problem DROP FOREIGN KEY hoja_problem_problem_id_fk');
        DB::statement('ALTER TABLE hoja_problem ADD CONSTRAINT hoja_problem_ibfk_2 FOREIGN KEY (problem_id) REFERENCES `pim_problems_31-01-26` (id) ON DELETE CASCADE');
    }
};
