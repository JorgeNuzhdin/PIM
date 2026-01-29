<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Eliminar tags huérfanos (problem_id que no existe en pim_problems)
$deleted = DB::delete('DELETE FROM problemas_tags WHERE problem_id NOT IN (SELECT id FROM pim_problems)');

echo "Se eliminaron {$deleted} registros huérfanos de problemas_tags\n";
