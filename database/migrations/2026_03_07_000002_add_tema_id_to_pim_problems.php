<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pim_problems', 'tema_id')) {
            return; // ya existe (añadida manualmente via browser)
        }
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->integer('tema_id')->nullable()->after('source'); // temas.id es int(11)
            $table->foreign('tema_id')->references('id')->on('temas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->dropForeign(['tema_id']);
            $table->dropColumn('tema_id');
        });
    }
};
