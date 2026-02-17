<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos', function (Blueprint $table) {
            $table->dropColumn('method_html');
        });
    }

    public function down(): void
    {
        Schema::table('metodos', function (Blueprint $table) {
            $table->text('method_html')->nullable()->after('method_tex');
        });
    }
};
