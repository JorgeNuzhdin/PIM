<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar la FK incorrecta que apunta a pim_topics
        Schema::table('topic_tema', function (Blueprint $table) {
            $table->dropForeign('topic_tema_ibfk_1');
        });

        // Asegurarse de que todos los valores existentes en topic_tema
        // también existan en tags (para que la nueva FK no falle)
        $orphans = DB::table('topic_tema')
            ->leftJoin('tags', 'topic_tema.topic_title', '=', 'tags.title')
            ->whereNull('tags.title')
            ->pluck('topic_tema.topic_title')
            ->unique();

        foreach ($orphans as $title) {
            DB::table('tags')->insert(['title' => $title]);
        }

        // Añadir la FK correcta apuntando a tags
        Schema::table('topic_tema', function (Blueprint $table) {
            $table->foreign('topic_title', 'topic_tema_fk_tags')
                  ->references('title')
                  ->on('tags')
                  ->onDelete('CASCADE')
                  ->onUpdate('CASCADE');
        });
    }

    public function down(): void
    {
        Schema::table('topic_tema', function (Blueprint $table) {
            $table->dropForeign('topic_tema_fk_tags');
        });

        Schema::table('topic_tema', function (Blueprint $table) {
            $table->foreign('topic_title', 'topic_tema_ibfk_1')
                  ->references('title')
                  ->on('pim_topics')
                  ->onDelete('CASCADE')
                  ->onUpdate('CASCADE');
        });
    }
};
