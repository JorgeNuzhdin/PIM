<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->dropColumn('pdf_file');
        });
    }

    public function down(): void
    {
        Schema::table('pim_problems', function (Blueprint $table) {
            $table->binary('pdf_file')->nullable();
        });
    }
};
