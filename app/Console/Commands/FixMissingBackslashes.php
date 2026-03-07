<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMissingBackslashes extends Command
{
    protected $signature   = 'problemas:fix-backslashes {--dry-run : Mostrar cambios sin aplicarlos}';
    protected $description = 'Añade \ antes de comandos LaTeX conocidos que aparecen sin él, SOLO dentro de entornos matemáticos';

    // Solo comandos que no pueden confundirse con palabras españolas.
    // Excluidos deliberadamente: sin, cos, tan, color, log, exp, max, min, etc.
    private const COMMANDS = [
        'overrightarrow', 'overleftarrow', 'overleftrightarrow',
        'overline', 'underline', 'widehat', 'widetilde',
        'overbrace', 'underbrace',
        'operatorname', 'displaystyle', 'textstyle', 'scriptstyle',
        'mathbb', 'mathbf', 'mathcal', 'mathit', 'mathsf', 'mathrm', 'mathscr', 'mathfrak',
        'textbf', 'textit', 'textrm', 'texttt',
        'dfrac', 'tfrac', 'cfrac',
        'binom', 'dbinom',
        'sqrt', 'frac',
        'sum', 'prod', 'int', 'oint', 'iint',
        'infty', 'partial',
        'alpha','beta','gamma','delta','epsilon','varepsilon','zeta','eta',
        'theta','vartheta','iota','kappa','lambda','mu','nu','xi','varpi',
        'rho','varrho','sigma','varsigma','tau','upsilon','phi','varphi','chi','psi','omega',
        'Gamma','Delta','Theta','Lambda','Xi','Pi','Sigma','Upsilon','Phi','Psi','Omega',
        'cdot','cdots','ldots','vdots','ddots',
        'leq','geq','neq','approx','equiv','sim','simeq','cong','propto',
        'cup','cap','subset','supset','subseteq','supseteq','setminus',
        'leftarrow','rightarrow','leftrightarrow','Leftarrow','Rightarrow','Leftrightarrow',
        'mapsto','hookrightarrow',
        'forall','exists','nexists','neg','land','lor','implies','iff',
        'emptyset','varnothing',
        'vec','hat','bar','tilde','dot','ddot','check','acute','grave','breve',
        'lfloor','rfloor','lceil','rceil','langle','rangle',
        'left','right',
        'begin','end',
        'quad','qquad','hspace','vspace',
        'textcolor',
        'boxed','underset','overset','stackrel',
        'pmod','bmod',
        'arcsin','arccos','arctan',
        'lim','gcd',
        'det','ker',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $columns = ['title', 'problem_tex', 'solution_tex'];
        $totalFixes = 0;

        $problems = DB::table('pim_problems')
            ->select('id', 'title', 'problem_tex', 'solution_tex')
            ->get();

        foreach ($problems as $problem) {
            $updates = [];

            foreach ($columns as $col) {
                $original = $problem->$col;
                if ($original === null) continue;

                $fixed = $this->fixBackslashes($original);
                if ($fixed !== $original) {
                    $updates[$col] = $fixed;
                    $totalFixes++;
                    if ($dryRun) {
                        $this->line("  [#{$problem->id}] $col:");
                        $this->line("    ANTES:  " . substr($original, 0, 120));
                        $this->line("    DESPUÉS:" . substr($fixed, 0, 120));
                    }
                }
            }

            if (!empty($updates) && !$dryRun) {
                DB::table('pim_problems')->where('id', $problem->id)->update($updates);
                $this->line("✓ Problema #{$problem->id} corregido (" . implode(', ', array_keys($updates)) . ')');
            }
        }

        if ($totalFixes === 0) {
            $this->info('No se encontraron problemas con backslashes faltantes.');
        } else {
            $verb = $dryRun ? 'Se encontrarían' : 'Se corrigieron';
            $this->info("$verb $totalFixes campo(s) en total.");
        }

        return 0;
    }

    /**
     * Aplica las correcciones SOLO dentro de entornos matemáticos:
     *   $$...$$   \[...\]   $...$   \(...\)
     */
    private function fixBackslashes(string $text): string
    {
        // Orden: $$ antes de $ para evitar que $ capture la mitad de $$
        return preg_replace_callback(
            '/(\$\$[\s\S]*?\$\$|\\\\\[[\s\S]*?\\\\\]|\$[^$\n]*?\$|\\\\\([\s\S]*?\\\\\))/u',
            fn($m) => $this->fixCommandsInMath($m[0]),
            $text
        ) ?? $text;
    }

    private function fixCommandsInMath(string $math): string
    {
        foreach (self::COMMANDS as $cmd) {
            $math = preg_replace(
                '/(?<!\\\\)\b' . preg_quote($cmd, '/') . '\b/',
                '\\' . $cmd,
                $math
            );
        }
        return $math;
    }
}
