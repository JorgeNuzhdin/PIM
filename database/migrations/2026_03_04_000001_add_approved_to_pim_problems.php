<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->boolean('approved')->default(true)->after('proponent_id');
        });
        // Todos los problemas existentes quedan marcados como aprobados
        DB::table('pim_problems')->update(['approved' => 1]);
    }

    public function down(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->dropColumn('approved');
        });
    }
};
