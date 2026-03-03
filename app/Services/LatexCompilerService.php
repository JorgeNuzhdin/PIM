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

        // Copiar paquetes locales (resources/tex/packages/) al directorio de compilación
        // Incluye .sty y .tex (algunos paquetes como listofitems usan archivos .tex compañeros)
        $packagesDir = resource_path('tex/packages');
        if (is_dir($packagesDir)) {
            foreach (glob($packagesDir . '/*.{sty,tex,fd}', GLOB_BRACE) as $pkgFile) {
                copy($pkgFile, $tempDir . '/' . basename($pkgFile));
            }
        }

        // Escribir imágenes (re-codificar PNGs para compatibilidad con libpng)
        foreach ($images as $name => $binaryData) {
            if (str_ends_with(strtolower($name), '.png')) {
                $img = @imagecreatefromstring($binaryData);
                if ($img) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    ob_start();
                    imagepng($img);
                    $binaryData = ob_get_clean();
                    imagedestroy($img);
                } else {
                    Log::warning("Imagen PNG corrupta o truncada, omitida: {$name} (" . strlen($binaryData) . " bytes)");
                    continue; // No escribir PNGs corruptos
                }
            }
            $imagePath = $tempDir . '/' . $name;
            $imageDir  = dirname($imagePath);
            if (!is_dir($imageDir)) {
                mkdir($imageDir, 0755, true);
            }
            file_put_contents($imagePath, $binaryData);
        }

        // Ruta a pdflatex
        $pdflatex = config('services.latex.pdflatex_path', 'pdflatex');
        $escapedDir = escapeshellarg($tempDir);
        $cmd = "cd {$escapedDir} && {$pdflatex} -interaction=nonstopmode document.tex 2>&1";

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
        $errorLines = [];
        if (!file_exists($pdfPath) && $logContent) {
            $lines = explode("\n", $logContent);
            foreach ($lines as $i => $line) {
                if (str_starts_with(trim($line), '!')) {
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

        $errorSummary = !empty($errorLines)
            ? implode(' | ', array_slice(array_filter($errorLines, fn($l) => $l !== '---'), 0, 3))
            : '';

        return [
            'pdf'          => file_exists($pdfPath) ? $pdfPath : null,
            'tempDir'      => $tempDir,
            'log'          => $logContent,
            'errorSummary' => $errorSummary,
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
