<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LatexCompilerService
{
    /**
     * Compila un archivo .tex a PDF usando pdflatex.
     *
     * @param string $texContent Contenido del archivo .tex
     * @param array $images Array asociativo [nombre => datos_binarios]
     * @return array{pdf: string|null, tempDir: string, log: string}
     */
    public function compile(string $texContent, array $images = []): array
    {
        $tempDir = storage_path('app/temp/latex_' . uniqid());
        mkdir($tempDir, 0755, true);

        // Escribir archivo .tex
        file_put_contents($tempDir . '/document.tex', $texContent);

        // Escribir imágenes
        foreach ($images as $name => $binaryData) {
            file_put_contents($tempDir . '/' . $name, $binaryData);
        }

        // Ruta a pdflatex
        $pdflatex = config('services.latex.pdflatex_path', 'pdflatex');
        $escapedDir = escapeshellarg($tempDir);
        $cmd = "cd {$escapedDir} && {$pdflatex} -interaction=nonstopmode -halt-on-error document.tex 2>&1";

        // Primera pasada
        exec($cmd, $output1, $returnCode1);
        Log::info("pdflatex pass 1: exit code {$returnCode1}");

        // Segunda pasada (para referencias, numeración, etc.)
        $output2 = [];
        exec($cmd, $output2, $returnCode2);
        Log::info("pdflatex pass 2: exit code {$returnCode2}");

        $pdfPath = $tempDir . '/document.pdf';
        $logContent = implode("\n", $output2);

        // Leer log de compilación si existe
        $texLog = $tempDir . '/document.log';
        if (file_exists($texLog)) {
            $logContent = file_get_contents($texLog);
        }

        // Extraer líneas de error del log (empiezan con !)
        if (!file_exists($pdfPath) && $logContent) {
            $errorLines = [];
            $lines = explode("\n", $logContent);
            foreach ($lines as $i => $line) {
                if (str_starts_with(trim($line), '!')) {
                    // Incluir la línea de error y las 2 siguientes para contexto
                    $errorLines[] = trim($line);
                    if (isset($lines[$i + 1])) $errorLines[] = trim($lines[$i + 1]);
                    if (isset($lines[$i + 2])) $errorLines[] = trim($lines[$i + 2]);
                    $errorLines[] = '---';
                }
            }
            if (!empty($errorLines)) {
                Log::error("Errores LaTeX encontrados:\n" . implode("\n", $errorLines));
            }
        }

        return [
            'pdf' => file_exists($pdfPath) ? $pdfPath : null,
            'tempDir' => $tempDir,
            'log' => $logContent,
        ];
    }

    /**
     * Elimina el directorio temporal y todos sus archivos.
     */
    public function cleanup(string $tempDir): void
    {
        if (!is_dir($tempDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($tempDir);
    }
}
