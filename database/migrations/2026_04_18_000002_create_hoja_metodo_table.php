<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hoja_metodo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hoja_id');
            $table->unsignedBigInteger('metodo_id');
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->foreign('hoja_id')->references('id')->on('hojas')->onDelete('cascade');
            $table->foreign('metodo_id')->references('id')->on('metodos')->onDelete('cascade');
            $table->index('hoja_id');
            $table->index('metodo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hoja_metodo');
    }
};
