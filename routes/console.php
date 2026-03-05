<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pim:check-blob-images', function () {
    $BLOB = 65535;

    // 1. Tipos de columna
    $this->line('=== Tipos de columna ===');
    foreach (['pim_figures', 'pim_figures_in_intros', 'metodo_figures'] as $t) {
        $col = DB::selectOne("SHOW COLUMNS FROM `{$t}` LIKE 'figure'");
        $type = $col ? strtolower($col->Type) : '???';
        $ok = $type === 'longblob';
        $this->line(($ok ? '✅' : '⚠️ ') . " {$t}.figure → {$type}" . ($ok ? '' : '  ← NO ES LONGBLOB'));
    }

    // 2. Imágenes truncadas en pim_figures (ligadas a problemas)
    $this->newLine();
    $this->line('=== pim_figures (imágenes de problemas) ===');
    $rows = DB::select("
        SELECT f.id, f.title, f.problem_id, LENGTH(f.figure) AS bytes,
               LEFT(p.problem_tex, 60) AS enunciado
        FROM pim_figures f
        LEFT JOIN pim_problems p ON p.id = f.problem_id
        WHERE LENGTH(f.figure) = ?
        ORDER BY f.problem_id
    ", [$BLOB]);
    if ($rows) {
        $this->table(['problem_id', 'fig.id', 'archivo', 'bytes', 'enunciado'], array_map(
            fn($r) => [$r->problem_id, $r->id, $r->title, $r->bytes, mb_strimwidth($r->enunciado ?? '', 0, 55, '…')],
            $rows
        ));
    } else { $this->line('  Ninguna.'); }

    // 3. Imágenes truncadas en pim_figures_in_intros (ligadas a hojas)
    $this->newLine();
    $this->line('=== pim_figures_in_intros (imágenes de hojas) ===');
    $rows = DB::select("
        SELECT f.id, f.title, f.intro_id, LENGTH(f.figure) AS bytes,
               COALESCE(s.title, '(sin título)') AS hoja
        FROM pim_figures_in_intros f
        LEFT JOIN pim_sheets s ON s.id = f.intro_id
        WHERE LENGTH(f.figure) = ?
        ORDER BY f.intro_id
    ", [$BLOB]);
    if ($rows) {
        $this->table(['intro_id', 'fig.id', 'archivo', 'bytes', 'hoja'], array_map(
            fn($r) => [$r->intro_id, $r->id, $r->title, $r->bytes, mb_strimwidth($r->hoja, 0, 55, '…')],
            $rows
        ));
    } else { $this->line('  Ninguna.'); }

    // 4. Imágenes truncadas en metodo_figures
    $this->newLine();
    $this->line('=== metodo_figures (imágenes de métodos) ===');
    $rows = DB::select("
        SELECT f.id, f.title, f.metodo_id, LENGTH(f.figure) AS bytes,
               COALESCE(m.title, '(sin título)') AS metodo
        FROM metodo_figures f
        LEFT JOIN metodos m ON m.id = f.metodo_id
        WHERE LENGTH(f.figure) = ?
        ORDER BY f.metodo_id
    ", [$BLOB]);
    if ($rows) {
        $this->table(['metodo_id', 'fig.id', 'archivo', 'bytes', 'método'], array_map(
            fn($r) => [$r->metodo_id, $r->id, $r->title, $r->bytes, mb_strimwidth($r->metodo, 0, 55, '…')],
            $rows
        ));
    } else { $this->line('  Ninguna.'); }

    // 5. Resumen
    $this->newLine();
    $total = DB::scalar("SELECT
        (SELECT COUNT(*) FROM pim_figures WHERE LENGTH(figure)=?) +
        (SELECT COUNT(*) FROM pim_figures_in_intros WHERE LENGTH(figure)=?) +
        (SELECT COUNT(*) FROM metodo_figures WHERE LENGTH(figure)=?)
    ", [$BLOB, $BLOB, $BLOB]);
    $total === 0
        ? $this->info('No hay imágenes truncadas. ✅')
        : $this->error("Total imágenes truncadas: {$total}");
})->purpose('Detecta imágenes truncadas al límite BLOB (65535 bytes)');
