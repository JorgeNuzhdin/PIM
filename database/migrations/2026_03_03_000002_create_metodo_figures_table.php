<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodo_figures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('metodo_id')->index();
            $table->string('title');
            $table->longText('figure');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodo_figures');
    }
};
