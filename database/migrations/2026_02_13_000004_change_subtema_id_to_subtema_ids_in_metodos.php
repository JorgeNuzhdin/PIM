<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the foreign key constraint first
        Schema::table('metodos', function (Blueprint $table) {
            $table->dropForeign(['subtema_id']);
        });

        // Rename column and change type
        Schema::table('metodos', function (Blueprint $table) {
            $table->renameColumn('subtema_id', 'subtema_ids');
        });

        Schema::table('metodos', function (Blueprint $table) {
            $table->string('subtema_ids', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('metodos', function (Blueprint $table) {
            $table->renameColumn('subtema_ids', 'subtema_id');
        });

        Schema::table('metodos', function (Blueprint $table) {
            $table->unsignedBigInteger('subtema_id')->nullable(false)->change();
        });

        Schema::table('metodos', function (Blueprint $table) {
            $table->foreign('subtema_id')->references('id')->on('subtemas')->onDelete('cascade');
        });
    }
};
