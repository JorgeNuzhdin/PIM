<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateTemas extends Command
{
    protected $signature   = 'problemas:populate-temas {--dry-run : Mostrar cambios sin aplicarlos}';
    protected $description = 'Rellena el campo tema_id en pim_problems a partir de las tags y la tabla topic_tema';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Cargar todos los temas para mostrar nombres en conflictos
        $temas = DB::table('temas')->pluck('tema', 'id'); // [id => nombre]

        // Cargar el mapa tag → tema_id desde topic_tema
        $tagToTema = DB::table('topic_tema')
            ->whereNotNull('tema_id')
            ->pluck('tema_id', 'topic_title'); // [tag => tema_id]

        // Cargar todos los problemas con sus tags
        $problems = DB::table('pim_problems')
            ->select('id', 'tema_id')
            ->get();

        $tagsByProblem = DB::table('problemas_tags')
            ->select('problem_id', 'tag')
            ->get()
            ->groupBy('problem_id');

        $set = 0;
        $skipped = 0;
        $conflicts = 0;

        foreach ($problems as $problem) {
            $tags = $tagsByProblem->get($problem->id, collect());

            // Mapear cada tag a su tema_id (ignorar tags sin correspondencia)
            $temaIds = $tags
                ->map(fn($t) => $tagToTema[$t->tag] ?? null)
                ->filter()
                ->unique()
                ->values();

            if ($temaIds->isEmpty()) {
                // Sin tags o ninguna con tema asignado
                $skipped++;
                continue;
            }

            if ($temaIds->count() === 1) {
                $newTema = $temaIds->first();

                if ($problem->tema_id === $newTema) {
                    $skipped++;
                    continue;
                }

                $temaName = $temas[$newTema] ?? "#{$newTema}";
                $this->line("  [#{$problem->id}] → Tema: {$temaName}");

                if (!$dryRun) {
                    DB::table('pim_problems')->where('id', $problem->id)->update(['tema_id' => $newTema]);
                }
                $set++;
            } else {
                // Conflicto: varias tags apuntan a temas distintos
                $conflicts++;
                $this->warn("\n  [#{$problem->id}] Conflicto — las tags apuntan a múltiples temas:");

                $tagList = $tags->map(fn($t) => $t->tag)->implode(', ');
                $this->line("    Tags: {$tagList}");

                $options = $temaIds->map(fn($id) => ($temas[$id] ?? "#{$id}") . " (id={$id})")->all();
                $options[] = 'Saltar (no cambiar)';

                $choice = $this->choice(
                    "  ¿Qué tema asignar al problema #{$problem->id}?",
                    $options,
                    count($options) - 1  // default: Saltar
                );

                if ($choice === 'Saltar (no cambiar)') {
                    $this->line('    → Saltado.');
                    $skipped++;
                    continue;
                }

                // Extraer el id del tema elegido desde el string "Nombre (id=X)"
                preg_match('/id=(\d+)\)$/', $choice, $m);
                $chosenId = (int) $m[1];
                $chosenName = $temas[$chosenId] ?? "#{$chosenId}";
                $this->line("    → Asignado: {$chosenName}");

                if (!$dryRun) {
                    DB::table('pim_problems')->where('id', $problem->id)->update(['tema_id' => $chosenId]);
                }
                $set++;
            }
        }

        $this->newLine();
        $verb = $dryRun ? 'Se asignarían' : 'Se asignaron';
        $this->info("{$verb} {$set} problema(s). Saltados: {$skipped}. Conflictos resueltos: {$conflicts}.");

        return 0;
    }
}
