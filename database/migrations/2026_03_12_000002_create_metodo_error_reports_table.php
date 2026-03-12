<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodo_error_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('metodo_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reporte');
            $table->char('tipo', 5)->default('00000');
            $table->boolean('solved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodo_error_reports');
    }
};
