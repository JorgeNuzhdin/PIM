<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('problema_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reporte');
            $table->char('tipo', 9)->default('000000000');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};
