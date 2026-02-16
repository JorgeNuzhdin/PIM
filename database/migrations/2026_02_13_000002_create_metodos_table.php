<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('method_tex');
            $table->longText('method_html')->nullable();
            $table->unsignedBigInteger('subtema_id');
            $table->integer('tema_id');
            $table->foreign('subtema_id')->references('id')->on('subtemas')->onDelete('cascade');
            $table->foreign('tema_id')->references('id')->on('temas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos');
    }
};
