<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
            $table->string('label')->nullable();
        });

        // Valor por defecto: acceso a 2 años académicos
        DB::table('settings')->insert([
            ['key' => 'access_years', 'value' => '2', 'label' => 'Años académicos visibles para usuarios básicos'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
