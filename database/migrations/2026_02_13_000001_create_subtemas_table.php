<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtemas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('tema_id');
            $table->foreign('tema_id')->references('id')->on('temas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtemas');
    }
};
