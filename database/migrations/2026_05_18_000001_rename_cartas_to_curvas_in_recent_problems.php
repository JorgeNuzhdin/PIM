<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 40 problemas más recientes por id DESC
        $recentIds = DB::table('pim_problems')
            ->orderByDesc('id')
            ->limit(40)
            ->pluck('id');

        if ($recentIds->isEmpty()) {
            return;
        }

        // Si un problema ya tiene 'curvas' además de 'cartas', borrar 'cartas'
        // para evitar duplicados al renombrar.
        $alreadyHaveCurvas = DB::table('problemas_tags')
            ->whereIn('problem_id', $recentIds)
            ->where('tag', 'curvas')
            ->pluck('problem_id');

        if ($alreadyHaveCurvas->isNotEmpty()) {
            DB::table('problemas_tags')
                ->whereIn('problem_id', $alreadyHaveCurvas)
                ->where('tag', 'cartas')
                ->delete();
        }

        // Renombrar 'cartas' → 'curvas' en los problemas restantes
        $renamed = DB::table('problemas_tags')
            ->whereIn('problem_id', $recentIds)
            ->where('tag', 'cartas')
            ->update(['tag' => 'curvas']);

        // Log informativo
        \Log::info("rename_cartas_to_curvas_in_recent_problems: renamed {$renamed} rows from 'cartas' to 'curvas' across the last 40 problems.");
    }

    public function down(): void
    {
        // No-op: revertir 'curvas' → 'cartas' afectaría tags legítimos
        // que ya existían como 'curvas' antes de esta migración.
    }
};
