<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('carrito');
    }
};