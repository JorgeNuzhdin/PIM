<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->dropColumn(['problem_html', 'solution_html']);
        });
    }

    public function down(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->text('problem_html')->nullable()->after('problem_tex');
            $table->text('solution_html')->nullable()->after('solution_tex');
        });
    }
};
