<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos', function (Blueprint $table) {
            $table->string('institution', 256)->default('PIM')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('metodos', function (Blueprint $table) {
            $table->dropColumn('institution');
        });
    }
};
