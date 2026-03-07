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

        // Copiar pim_topics → tags (los que no existan ya)
        $pimTopics = DB::table('pim_topics')->pluck('title');
        foreach ($pimTopics as $title) {
            $exists = DB::table('tags')->where('title', $title)->exists();
            if (!$exists) {
                DB::table('tags')->insert(['title' => $title]);
            }
        }

        // Copiar también cualquier valor de topic_tema que no esté en tags
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
