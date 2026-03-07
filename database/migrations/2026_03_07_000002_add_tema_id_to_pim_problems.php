<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->unsignedBigInteger('tema_id')->nullable()->after('source');
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
