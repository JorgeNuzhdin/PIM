<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckBlobImages extends Command
{
    protected $signature   = 'pim:check-blob-images';
    protected $description = 'Detecta imágenes truncadas (antiguo BLOB, 65535 bytes) en las tres tablas de figuras';

    // Límite exacto de BLOB de MySQL
    private const BLOB_LIMIT = 65535;

    public function handle(): int
    {
        $this->checkColumnType('pim_figures',          'figure');
        $this->checkColumnType('pim_figures_in_intros', 'figure');
        $this->checkColumnType('metodo_figures',        'figure');

        $this->newLine();

        $totalCorruptas = 0;
        $totalCorruptas += $this->checkTable(
            table:     'pim_figures',
            idCol:     'problem_id',
            label:     'Problema',
            joinQuery: 'LEFT JOIN pim_problems p ON p.id = f.problem_id',
            nameCol:   'COALESCE(LEFT(p.problem_tex, 60), "(sin enunciado)")',
            nameAlias: 'enunciado',
        );

        $totalCorruptas += $this->checkTable(
            table:     'pim_figures_in_intros',
            idCol:     'intro_id',
            label:     'Hoja',
            joinQuery: 'LEFT JOIN pim_sheets s ON s.id = f.intro_id',
            nameCol:   'COALESCE(s.title, "(sin título)")',
            nameAlias: 'titulo_hoja',
        );

        $totalCorruptas += $this->checkTable(
            table:     'metodo_figures',
            idCol:     'metodo_id',
            label:     'Método',
            joinQuery: 'LEFT JOIN metodos m ON m.id = f.metodo_id',
            nameCol:   'COALESCE(m.title, "(sin título)")',
            nameAlias: 'titulo_metodo',
        );

        $this->newLine();
        if ($totalCorruptas === 0) {
            $this->info('No se encontraron imágenes truncadas. ✅');
        } else {
            $this->error("Total imágenes truncadas: {$totalCorruptas}");
            $this->line('Estas imágenes están cortadas a 65535 bytes y deben resubirse.');
        }

        return Command::SUCCESS;
    }

    private function checkColumnType(string $table, string $column): void
    {
        $row = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (!$row) {
            $this->warn("{$table}.{$column} → columna no encontrada");
            return;
        }

        $type = strtolower($row->Type);
        $ok   = $type === 'longblob';
        $icon = $ok ? '✅' : '⚠️ ';
        $this->line("{$icon} {$table}.{$column} → <comment>{$row->Type}</comment>" . ($ok ? '' : '  ← NECESITA MIGRAR A LONGBLOB'));
    }

    private function checkTable(
        string $table,
        string $idCol,
        string $label,
        string $joinQuery,
        string $nameCol,
        string $nameAlias,
    ): int {
        $sql = "
            SELECT f.id, f.title, f.{$idCol}, LENGTH(f.figure) AS size_bytes,
                   {$nameCol} AS {$nameAlias}
            FROM `{$table}` f
            {$joinQuery}
            WHERE LENGTH(f.figure) = ?
            ORDER BY f.{$idCol}
        ";

        $rows = DB::select($sql, [self::BLOB_LIMIT]);

        $this->newLine();
        $this->line("=== {$table} ({$label}s con imágenes truncadas) ===");

        if (empty($rows)) {
            $this->line('  Ninguna.');
            return 0;
        }

        $headers = [$label . ' ID', 'fig.id', 'Nombre archivo', 'Bytes', $nameAlias];
        $data = array_map(fn($r) => [
            $r->{$idCol},
            $r->id,
            $r->title,
            number_format($r->size_bytes),
            mb_strimwidth($r->{$nameAlias}, 0, 55, '…'),
        ], $rows);

        $this->table($headers, $data);
        return count($rows);
    }
}
