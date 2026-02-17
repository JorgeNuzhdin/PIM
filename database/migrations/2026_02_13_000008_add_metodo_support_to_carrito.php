<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recrear la tabla carrito desde cero con soporte para metodos
        Schema::dropIfExists('carrito');

        Schema::create('carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('problema_id')->nullable();
            $table->unsignedBigInteger('metodo_id')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->foreign('problema_id')->references('id')->on('pim_problems')->onDelete('cascade');
            $table->foreign('metodo_id')->references('id')->on('metodos')->onDelete('cascade');
            $table->index(['user_id', 'problema_id']);
            $table->index(['user_id', 'metodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito');

        Schema::create('carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('problema_id');
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'problema_id']);
            $table->foreign('problema_id')->references('id')->on('pim_problems')->onDelete('cascade');
        });
    }
};
