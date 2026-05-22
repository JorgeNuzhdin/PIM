<?php

namespace App\Helpers;

class LatexHelper
{
    private static $countDefinition = 0;
    private static $countExemple = 0;
    private static $countTheorem = 0;
    private static $countLemma = 0;
    private static $countReto = 0;
    private static $countRetoResuelto = 0;
    private static $isPreambleContext = false;
    private static $debugMessages = [];
    private static $tikzBlocks = [];
    private static $mathBlocks = [];
    private static function debug($message, $data = null)
    {
        $debugInfo = $message;
        if ($data !== null) {
            $debugInfo .= ': ' . json_encode($data);
        }
        self::$debugMessages[] = $debugInfo;
    }

    /**
     * Limpia comandos LaTeX de una cadena corta (p. ej. el campo "fuente") para mostrarla como texto plano.
     * - Elimina envoltorios como \textit{X}, \textbf{X}, \emph{X}, \mathrm{X}... manteniendo el contenido
     * - Convierte espacios LaTeX (\,, \;, \:, \!, \ , \\, ~) en un espacio normal
     * - Interpreta el patrón "Letra mayúscula + . + \" como inicial (ej. "A.\ Skopenkov" -> "A. Skopenkov")
     * - Elimina llaves sueltas y colapsa espacios múltiples
     */
    public static function cleanLatexForDisplay(string $source): string
    {
        $clean = $source;

        $clean = preg_replace('/\\\\(?:textit|textbf|textsl|textsc|emph|mathrm|mathit|mathbf|texttt|textsf)\s*\{([^{}]*)\}/u', '$1', $clean);

        $clean = preg_replace('/\\\\\\\\/', ' ', $clean);

        $clean = preg_replace('/\\\\[,;:! ]/', ' ', $clean);

        $clean = preg_replace('/([A-ZÁÉÍÓÚÑ])\.\\\\\s*/u', '$1. ', $clean);

        $clean = str_replace('~', ' ', $clean);

        $clean = str_replace(['{', '}'], '', $clean);

        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    public static function getDebugScript()
    {
        if (empty(self::$debugMessages)) {
            return '';
        }
        
        $script = '<script>console.group("LatexHelper Debug");';
        foreach (self::$debugMessages as $msg) {
            $script .= 'console.log(' . json_encode($msg) . ');';
        }
        $script .= 'console.groupEnd();</script>';
        
        self::$debugMessages = []; // Limpiar para la siguiente llamada
        return $script;
    }


    private static function fromAtoB($a, $b, $t)
    {
        $start = strpos($t, $a);
        if ($start !== false) {
            $beforeA = substr($t, 0, $start);
            $afterA = substr($t, $start + strlen($a));
            $end = strpos($afterA, $b);
            if ($end !== false) {
                $inside = substr($afterA, 0, $end);
                $afterB = substr($afterA, $end + strlen($b));
                return ['before' => $beforeA, 'after' => $afterB, 'inside' => $inside];
            } else {
                return ['before' => $t, 'after' => '', 'inside' => ''];
            }
        } else {
            return ['before' => $t, 'after' => '', 'inside' => ''];
        }
    }

    /**
     * Extrae contenido de un comando LaTeX contando llaves anidadas.
     * Por ejemplo: \exercise{texto con \frac{1}{2} dentro}
     * Devuelve todo el contenido hasta la llave de cierre que corresponde.
     */
    private static function extractBracedContent($command, $t)
    {
        $pattern = $command . '{';
        $start = strpos($t, $pattern);

        if ($start === false) {
            return ['before' => $t, 'after' => '', 'inside' => ''];
        }

        $beforeCmd = substr($t, 0, $start);
        $contentStart = $start + strlen($pattern);
        $remaining = substr($t, $contentStart);

        // Contar llaves para encontrar la de cierre correcta
        $braceCount = 1;
        $pos = 0;
        $len = strlen($remaining);

        while ($pos < $len && $braceCount > 0) {
            $char = $remaining[$pos];

            // Ignorar llaves escapadas
            if ($pos > 0 && $remaining[$pos - 1] === '\\') {
                $pos++;
                continue;
            }

            if ($char === '{') {
                $braceCount++;
            } elseif ($char === '}') {
                $braceCount--;
            }

            if ($braceCount > 0) {
                $pos++;
            }
        }

        if ($braceCount === 0) {
            $inside = substr($remaining, 0, $pos);
            $after = substr($remaining, $pos + 1); // +1 para saltar la llave de cierre
            return ['before' => $beforeCmd, 'after' => $after, 'inside' => $inside];
        }

        // Si no se encontró la llave de cierre, devolver vacío
        return ['before' => $t, 'after' => '', 'inside' => ''];
    }

 
private static function getIm($image)
{

    $pattern = '/\\\\includegraphics\s*\[(.*?)\]\s*\{(.*?)\}/';
    if (preg_match($pattern, $image, $matches)) {

        $options = $matches[1];
        $filename = $matches[2];

        // Determinar el ancho (permite espacios alrededor de =)
        $width = 100;

        if (preg_match('/width\s*=\s*([\d.]+)/', $options, $widthMatch)) {
            $width = floatval($widthMatch[1]) * 100;
        }
        elseif (preg_match('/scale\s*=\s*([\d.]+)/', $options, $scaleMatch)) {
            $width = floatval($scaleMatch[1]) * 100;
          }
        
        $imName = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $filename);
        $imName = trim($imName);

        // Buscar primero en pim_figures (problemas)
        $figure = \App\Models\Figure::where('title', $filename)->first();

        if (!$figure) {
            $figure = \App\Models\Figure::where('title', $imName)->first();
        }

        if (!$figure && !str_ends_with($filename, '.pdf')) {
            $figure = \App\Models\Figure::where('title', $imName . '.pdf')->first();
        }

        // Si no se encuentra, buscar en pim_figures_in_intros (preámbulos de hojas)
        if (!$figure) {
            $figure = \App\Models\FigureInIntro::where('title', $filename)->first();
        }

        if (!$figure) {
            $figure = \App\Models\FigureInIntro::where('title', $imName)->first();
        }

        if (!$figure && !str_ends_with($filename, '.pdf')) {
            $figure = \App\Models\FigureInIntro::where('title', $imName . '.pdf')->first();
        }
        
          if ($figure && $figure->figure) {
    
    try {
        // Detectar el tipo MIME real de la imagen
        $imageData = $figure->figure;
        
        // Obtener los primeros bytes para detectar el formato
        $header = substr($imageData, 0, 4);
        
        // Detectar tipo de imagen por magic bytes
        if (substr($header, 0, 2) === "\xFF\xD8") {
            $mimeType = 'image/jpeg';
        } elseif (substr($header, 0, 4) === "\x89PNG") {
            $mimeType = 'image/png';
        } elseif (substr($header, 0, 4) === '%PDF') {
            $mimeType = 'application/pdf';
        } elseif (substr($header, 0, 3) === 'GIF') {
            $mimeType = 'image/gif';
        } else {
            $mimeType = 'image/png'; // fallback
        }
        
          
        $imgSrc = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        
        // Si es PDF, no se puede mostrar inline, dar mensaje
        // Si es PDF, mostrarlo con un embed o iframe
    if ($mimeType === 'application/pdf') {
    $pdfSrc = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    return '<div style="display:flex; justify-content:center; margin: 1rem 0;">
                <embed src="' . $pdfSrc . '" type="application/pdf" width="' . $width . '%" height="500px" style="max-width: 100%;" />
            </div>';
}
        
        return '<img src="' . $imgSrc . '" width="' . $width . '%" class="latex-image" style="max-width: 100%; height: auto;" />';
    } catch (\Exception $e) {
         return '<div style="border: 1px solid red; padding: 10px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
        
        
        return '<div style="border: 1px solid orange; padding: 10px; color: orange;">No encontrada: ' . htmlspecialchars($filename) . '</div>';
    }
    
     return '';
}

private static function getImSimple($filename)
{
    $imName = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $filename);

    // Buscar primero en pim_figures (problemas)
    $figure = \App\Models\Figure::where('title', $filename)->first();

    if (!$figure) {
        $figure = \App\Models\Figure::where('title', $imName)->first();
    }

    if (!$figure && !str_ends_with($filename, '.pdf')) {
        $figure = \App\Models\Figure::where('title', $imName . '.pdf')->first();
    }

    // Si no se encuentra, buscar en pim_figures_in_intros (preámbulos de hojas)
    if (!$figure) {
        $figure = \App\Models\FigureInIntro::where('title', $filename)->first();
    }

    if (!$figure) {
        $figure = \App\Models\FigureInIntro::where('title', $imName)->first();
    }

    if (!$figure && !str_ends_with($filename, '.pdf')) {
        $figure = \App\Models\FigureInIntro::where('title', $imName . '.pdf')->first();
    }

    if ($figure && $figure->figure) {
        try {
            $imgSrc = 'data:image/png;base64,' . base64_encode($figure->figure);
            return '<img src="' . $imgSrc . '" class="latex-image" style="max-width: 80%; height: auto;" />';
        } catch (\Exception $e) {
            return '<div style="border: 1px solid red; padding: 10px; color: red;">Error: ' . htmlspecialchars($filename) . '</div>';
        }
    }
    
    return '<div style="border: 1px solid orange; padding: 10px; color: orange;">No encontrada: ' . htmlspecialchars($filename) . '</div>';
}


    /**
     * Extrae el contenido del segundo argumento de \parbox[opts]{width}{content}.
     * Salta args opcionales [...] y el primer {width}, devuelve lo que hay en {content}.
     */
    private static function stripParboxWrapper(string $box): string
    {
        $rest = ltrim($box);
        if (!str_starts_with($rest, '\parbox')) return $box;
        $rest = substr($rest, strlen('\parbox'));

        // Saltar args opcionales [...]
        while (preg_match('/^\s*\[/', $rest)) {
            $rest = ltrim($rest);
            $close = strpos($rest, ']');
            if ($close !== false) {
                $rest = substr($rest, $close + 1);
            } else break;
        }

        // Saltar primer {width} contando llaves anidadas
        $rest = ltrim($rest);
        if (isset($rest[0]) && $rest[0] === '{') {
            $count = 1; $i = 1; $len = strlen($rest);
            while ($i < $len && $count > 0) {
                if ($rest[$i] === '{') $count++;
                elseif ($rest[$i] === '}') $count--;
                $i++;
            }
            $rest = substr($rest, $i);
        }

        // Extraer contenido del segundo {content}
        $rest = ltrim($rest);
        if (isset($rest[0]) && $rest[0] === '{') {
            $count = 1; $i = 1; $len = strlen($rest);
            while ($i < $len && $count > 0) {
                if ($rest[$i] === '{') $count++;
                elseif ($rest[$i] === '}') $count--;
                if ($count > 0) $i++;
            }
            return substr($rest, 1, $i - 1);
        }

        return $box;
    }

    /**
     * Procesa grupos consecutivos de \fbox{...} y los convierte en divs con borde.
     * Si hay varios \fbox seguidos (separados solo por espacios/saltos de línea),
     * los agrupa en un contenedor flex para mostrarlos en columnas.
     * Soporta contenido con o sin \begin{minipage}...\end{minipage}.
     */
    private static function processConsecutiveFboxes(string $t): string
    {
        $output = '';
        $pos = 0;
        $len = strlen($t);

        while ($pos < $len) {
            $fboxPos = strpos($t, '\fbox{', $pos);
            if ($fboxPos === false) {
                $output .= substr($t, $pos);
                break;
            }

            // Texto antes del primer \fbox
            $output .= substr($t, $pos, $fboxPos - $pos);

            // Recoger todos los \fbox{} consecutivos (separados solo por espacios/newlines)
            $boxes = [];
            $curPos = $fboxPos;

            while ($curPos < $len && substr($t, $curPos, 6) === '\fbox{') {
                // Extraer contenido entre llaves contando llaves anidadas
                $braceStart = $curPos + 5; // posición de la {
                $braceCount = 1;
                $i = $braceStart + 1;
                while ($i < $len && $braceCount > 0) {
                    if ($t[$i] === '{') $braceCount++;
                    elseif ($t[$i] === '}') $braceCount--;
                    $i++;
                }
                // $i apunta al carácter después del }
                $boxContent = substr($t, $braceStart + 1, $i - $braceStart - 2);
                $boxes[] = $boxContent;
                $curPos = $i;

                // Saltar espacios/newlines para ver si hay otro \fbox seguido
                while ($curPos < $len && ctype_space($t[$curPos])) {
                    $curPos++;
                }
            }

            // Solo procesar como cajas con borde si al menos uno contiene \begin{minipage}
            // o \parbox, o si hay más de uno (varios \fbox seguidos).
            // Si es un único \fbox{texto corto} sin wrapper, dejarlo sin procesar
            // (MathJax puede estar manejándolo dentro de modo matemático).
            $hasWrapper = false;
            foreach ($boxes as $box) {
                if (strpos($box, '\begin{minipage}') !== false ||
                    preg_match('/^\s*\\\\parbox/s', $box)) {
                    $hasWrapper = true;
                    break;
                }
            }
            $isMultiple = count($boxes) > 1;

            if (!$hasWrapper && !$isMultiple) {
                // Devolver el \fbox{} original sin procesar
                $output .= '\fbox{' . $boxes[0] . '}';
                $pos = $curPos;
                continue;
            }

            // Procesar el contenido de cada caja
            $processedBoxes = [];
            foreach ($boxes as $box) {
                $box = trim($box);
                // Eliminar \parbox[opts]{width}{ wrapper y extraer contenido del segundo {}
                if (preg_match('/^\\\\parbox/s', $box)) {
                    $box = self::stripParboxWrapper($box);
                }
                // Eliminar \begin{minipage}[opt][opt]{width} (con 0, 1 o 2 args opcionales)
                $box = preg_replace('/^\\\\begin\{minipage\}(\[[^\]]*\])*\{[^}]*\}\s*/s', '', $box);
                // Eliminar \end{minipage} al final
                $box = preg_replace('/\\\\end\{minipage\}\s*$/s', '', $box);
                // Eliminar [0.5em], [1cm], [3pt]... dondequiera que aparezcan (espaciados LaTeX)
                $box = preg_replace('/\[[\d.]+\s*(em|pt|cm|mm|ex|in|pc|bp|sp)\]/', '', $box);
                $processedBoxes[] = trim($box);
            }

            // Renderizar
            $boxStyle = 'border: 1px solid #333; padding: 0.75rem; box-sizing: border-box;';
            if (count($processedBoxes) === 1) {
                $output .= '<div style="display:inline-block; ' . $boxStyle . ' margin: 0.25rem;">'
                         . $processedBoxes[0] . '</div>';
            } else {
                $output .= '<div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin:0.5rem 0;">';
                foreach ($processedBoxes as $box) {
                    $output .= '<div style="' . $boxStyle . ' flex:1; min-width:100px;">' . $box . '</div>';
                }
                $output .= '</div>';
            }

            $pos = $curPos;
        }

        return $output;
    }

    public static function toHtml($t)
    {
        if ($t == '') return '';

        $style = "border: 1px solid; text-align: center; vertical-align: center";
        $style_def = "border-left:solid #000; padding-left:15px; margin-left:10px;";
        $style_th = "border:solid #000; padding:10px; margin:10px;";
        $style_ex = 'border-left: 2px solid red; padding-left:15px; margin-left:10px;';

        // PRIMERO: Eliminar entornos tikzcd (tikzjax no los soporta)
        $t = preg_replace('/\\\\begin\{tikzcd\}.*?\\\\end\{tikzcd\}/s', '', $t);

        // Extraer bloques TikZ para protegerlos del escape de < y >
        self::$tikzBlocks = [];
        $tikzIndex = 0;
        while (($startPos = strpos($t, '\begin{tikzpicture}')) !== false) {
            $endPos = strpos($t, '\end{tikzpicture}', $startPos);
            if ($endPos !== false) {
                $endPos += strlen('\end{tikzpicture}');
                $tikzContent = substr($t, $startPos, $endPos - $startPos);
                $placeholder = "###TIKZ_BLOCK_{$tikzIndex}###";
                self::$tikzBlocks[$placeholder] = '<div class="tikz" style="display:flex; justify-content:center"><script type="text/tikz">' . $tikzContent . '</script></div>';
                $t = substr($t, 0, $startPos) . $placeholder . substr($t, $endPos);
                $tikzIndex++;
            } else {
                break; // No se encontró \end{tikzpicture}, salir del bucle
            }
        }

        // \vec{} y \Vec{} → \overrightarrow{} (dentro del math, antes de protegerlo)
        $t = str_replace(['\vec{', '\Vec{'], '\overrightarrow{', $t);

        // Extraer bloques math para protegerlos de transformaciones de texto (\textbf, \underline, etc.)
        self::$mathBlocks = [];
        $mathIndex = 0;
        // Primero \[...\] y \(...\)
        // Si contiene \begin{tabular}, NO es math: tabular no es válido dentro de \[\];
        // dejarlo intacto para que el handler de tabular lo procese normalmente.
        $t = preg_replace_callback('/\\\\\[[\s\S]*?\\\\\]/s', function ($m) use (&$mathIndex) {
            if (strpos($m[0], '\begin{tabular}') !== false) {
                // Eliminar los delimitadores \[ \] y dejar el contenido como texto
                return substr($m[0], 2, -2);
            }
            $placeholder = "###MATH_{$mathIndex}###";
            self::$mathBlocks[$placeholder] = $m[0];
            $mathIndex++;
            return $placeholder;
        }, $t);
        $t = preg_replace_callback('/\\\\\([\s\S]*?\\\\\)/s', function ($m) use (&$mathIndex) {
            $placeholder = "###MATH_{$mathIndex}###";
            self::$mathBlocks[$placeholder] = $m[0];
            $mathIndex++;
            return $placeholder;
        }, $t);
        // Luego $$...$$ (antes de $...$)
        // Si contiene \begin{tabular}, NO es math: dejar el contenido para el handler de tabular.
        $t = preg_replace_callback('/\$\$[\s\S]*?\$\$/s', function ($m) use (&$mathIndex) {
            if (strpos($m[0], '\begin{tabular}') !== false) {
                return substr($m[0], 2, -2);
            }
            $placeholder = "###MATH_{$mathIndex}###";
            self::$mathBlocks[$placeholder] = $m[0];
            $mathIndex++;
            return $placeholder;
        }, $t);
        // Finalmente $...$ (inline math)
        $t = preg_replace_callback('/\$[^\$]+?\$/s', function ($m) use (&$mathIndex) {
            $placeholder = "###MATH_{$mathIndex}###";
            self::$mathBlocks[$placeholder] = $m[0];
            $mathIndex++;
            return $placeholder;
        }, $t);

        // \qedhere inside math blocks causes MathJax errors — strip it there; text-mode \qedhere handled later
        foreach (self::$mathBlocks as &$mathContent) {
            $mathContent = str_replace('\qedhere', '', $mathContent);
        }
        unset($mathContent);

          $t = str_replace(['u000au000au000au000a', 'u000au000au000a', 'u000au000a', 'u000a', 'u000d', 'u0009'], ["\n\n", "\n\n", "\n\n", "\n", "\r", "\t"], $t);
    $t = str_replace(['\u000a\u000a\u000a\u000a', '\u000a\u000a\u000a', '\u000a\u000a', '\u000a', '\u000d', '\u0009'], ["\n\n", "\n\n", "\n\n", "\n", "\r", "\t"], $t);
    
    // Normalizar finales de línea: \r\n y \r (Mac/Windows) → \n
    $t = str_replace(["\r\n", "\r"], "\n", $t);

    // Una línea "vacía" (solo whitespace) debe contar como salto de párrafo:
    // colapsar líneas-de-solo-whitespace a una línea vacía pura
    $t = preg_replace("/\n[ \t]+\n/", "\n\n", $t);

    // Eliminar múltiples saltos de línea consecutivos
    $t = preg_replace("/\n{3,}/", "\n\n", $t);

    // Marcar los saltos de párrafo con un placeholder PRONTO
    // para que ningún preg_replace posterior con \s* los devore
    $t = str_replace("\n\n", '###PARABREAK###', $t);

     $t = str_replace('\%', 'PCTG', $t);

        // Proteger < y > del contenido original ANTES de generar HTML
        // Se convertirán a &lt; y &gt; al final
        $t = str_replace('<', '&&&LT&&&', $t);
        $t = str_replace('>', '&&&GT&&&', $t);

        // Eliminar comentarios: tanto al inicio de línea como inline (después de %)
        $t = preg_replace('/^%.*$/m', '', $t);  // Comentarios al inicio de línea
        $t = preg_replace('/(?<!\\\\)%.*$/m', '', $t);  // Comentarios inline (% hasta fin de línea)

        // Eliminar \idtitulo{...} completamente (contenido incluido, con llaves anidadas)
        $idtitulo = self::extractBracedContent('\idtitulo', $t);
        while ($idtitulo['inside'] != '') {
            $t = $idtitulo['before'] . $idtitulo['after'];
            $idtitulo = self::extractBracedContent('\idtitulo', $t);
        }

        // Eliminar patrones \+Mayúscula (como \J, \R, \RM, \N\M, etc.)
        // Incluye patrones seguidos de espacio, otro comando, o fin de línea
        $t = preg_replace('/\\\\[A-Z]+(?=\s|\\\\|$)/', '', $t);

        // Comandos LaTeX que MathJax no reconoce - convertir a equivalentes
        $t = str_replace('\degree', '^\\circ', $t);  // \degree → ^∘
        $t = str_replace('\Vec', '\vec', $t);        // \Vec → \vec (MathJax usa minúscula)
        $t = str_replace('\modd', '\pmod', $t);        // modular (MathJax usa pmod)
        $t = str_replace('\mcd', '\operatorname{mcd}', $t);  // \mcd → operatorname para MathJax
        $t = str_replace('\mcm', '\operatorname{mcm}', $t);  // \mcm → operatorname para MathJax
        $t = preg_replace('/\\\\arcsen\b/', '\arcsin', $t);  // \arcsen → \arcsin
        $t = preg_replace('/\\\\sen\b/', '\sin', $t);        // \sen → \sin
        $t = preg_replace('/\\\\checkmark\b/', '✓', $t);    // \checkmark → ✓
        $t = preg_replace('/\\\\qed\b/', '■', $t);           // \qed → ■

        // Eliminar comandos LaTeX que no tienen equivalente en HTML
        // Comandos de espaciado vertical
        $t = preg_replace('/\\\\noindent\s*/', '', $t);
        $t = preg_replace('/\\\\vspace\*?\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\vskip\s*[^\s]+/', '', $t);
        $t = preg_replace('/\\\\smallskip\s*/', '', $t);
        $t = preg_replace('/\\\\medskip\s*/', '', $t);
        $t = preg_replace('/\\\\bigskip\s*/', '', $t);
        $t = preg_replace('/\\\\vfill\s*/', '', $t);

        // Comandos de espaciado horizontal
        $t = preg_replace('/\\\\hspace\*?\{[^}]*\}/', ' ', $t);
        $t = preg_replace('/\\\\hfill\s*/', ' ', $t);
        $t = preg_replace('/\\\\phantom\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\strut\s*/', '', $t);

        // Comandos de salto de página (no tienen sentido en HTML)
        $t = preg_replace('/\\\\newpage\s*/', '', $t);
        $t = preg_replace('/\\\\pagebreak\s*/', '', $t);
        $t = preg_replace('/\\\\clearpage\s*/', '', $t);
        $t = preg_replace('/\\\\cleardoublepage\s*/', '', $t);

        // Comandos de configuración que no afectan la visualización
        $t = preg_replace('/\\\\setlength\{[^}]*\}\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\addtolength\{[^}]*\}\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\setcounter\{[^}]*\}\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\addtocounter\{[^}]*\}\{[^}]*\}/', '', $t);

        // Comandos de formato de párrafo
        $t = preg_replace('/\\\\indent\s*/', '', $t);
        $t = preg_replace('/\\\\centering\s*/', '', $t);
        $t = preg_replace('/\\\\raggedright\s*/', '', $t);
        $t = preg_replace('/\\\\raggedleft\s*/', '', $t);

        // Convertir \newline y \linebreak a <br>
        $t = preg_replace('/\\\\newline\s*/', '<br>', $t);
        $t = preg_replace('/\\\\linebreak\s*/', '<br>', $t);

        // Protect \\ inside tabular/array/align environments before converting free-standing \\
        // Replace \begin{ENV}...\end{ENV} content with placeholders so \\ inside is not touched
        // (tabular is handled separately; other environments like align are already math-protected)
        // We only need to protect \begin{tabular}...\end{tabular} since align/array are in math blocks
        $tabularPlaceholders = [];
        $tabularIdx = 0;
        $t = preg_replace_callback(
            '/\\\\begin\{tabular\}[\s\S]*?\\\\end\{tabular\}/s',
            function($m) use (&$tabularPlaceholders, &$tabularIdx) {
                $key = "###TABULAR_{$tabularIdx}###";
                $tabularPlaceholders[$key] = $m[0];
                $tabularIdx++;
                return $key;
            },
            $t
        );

        // Convertir \\ (doble backslash) a salto de línea fuera de tablas/matrices
        // \\[4pt] etc. → <br> with vertical margin; plain \\ → <br>
        $t = preg_replace_callback(
            '/\\\\\\\\\[([0-9]+(?:\.[0-9]+)?(?:pt|em|ex|mm|cm|in|px))\]\s*/',
            function ($m) { return '<br style="margin-bottom:' . $m[1] . '">'; },
            $t
        );
        $t = preg_replace('/\\\\\\\\\s*/', '<br>', $t);

        // Restore tabular blocks
        foreach ($tabularPlaceholders as $key => $original) {
            $t = str_replace($key, $original, $t);
        }

        // Eliminar \label{...} y \ref{...} que no funcionan en HTML
        $t = preg_replace('/\\\\label\{[^}]*\}/', '', $t);

        // Eliminar captions sueltos (fuera de figuras ya procesadas)
        $t = preg_replace('/\\\\caption\{[^}]*\}/', '', $t);

        // Eliminar comandos de bibliografía
        $t = preg_replace('/\\\\cite\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\bibliography\{[^}]*\}/', '', $t);
        $t = preg_replace('/\\\\bibliographystyle\{[^}]*\}/', '', $t);

        // Tilde como acento (\~n → ñ) — debe ir ANTES de ~ → &nbsp;
        $t = str_replace('\~{n}', 'ñ', $t);  $t = str_replace('\~{N}', 'Ñ', $t);
        $t = str_replace('\~{a}', 'ã', $t);  $t = str_replace('\~{A}', 'Ã', $t);
        $t = str_replace('\~{o}', 'õ', $t);  $t = str_replace('\~{O}', 'Õ', $t);
        $t = str_replace('\~n',   'ñ', $t);  $t = str_replace('\~N',   'Ñ', $t);
        $t = str_replace('\~a',   'ã', $t);  $t = str_replace('\~A',   'Ã', $t);
        $t = str_replace('\~o',   'õ', $t);  $t = str_replace('\~O',   'Õ', $t);

        // Convertir caracteres especiales de LaTeX
        $t = str_replace('~', '&nbsp;', $t);  // Espacio no separable
        $t = str_replace('\quad', '&emsp;', $t);  // Espacio cuádruple (~1em)
        $t = str_replace('\qquad', '&emsp;&emsp;', $t);  // Espacio doble cuádruple (~2em)
        $t = str_replace('---', '—', $t);     // Em dash (primero, más largo)
        $t = str_replace('--', '–', $t);      // En dash

        // Comillas tipográficas LaTeX
        $t = str_replace('``', '"', $t);      // Comillas de apertura
        $t = str_replace("''", '"', $t);      // Comillas de cierre

        // Eliminar \mbox{} pero mantener contenido
        $mbox = self::fromAtoB('\mbox{', '}', $t);
        while ($mbox['inside'] != '') {
            $t = $mbox['before'] . $mbox['inside'] . $mbox['after'];
            $mbox = self::fromAtoB('\mbox{', '}', $t);
        }

        // Convertir \emph{} a cursiva
        $emph = self::fromAtoB('\emph{', '}', $t);
        while ($emph['inside'] != '') {
            $t = $emph['before'] . '<em>' . $emph['inside'] . '</em>' . $emph['after'];
            $emph = self::fromAtoB('\emph{', '}', $t);
        }

        // \textbf{} → <strong>
        $textbf = self::fromAtoB('\textbf{', '}', $t);
        while ($textbf['inside'] != '') {
            $t = $textbf['before'] . '<strong>' . $textbf['inside'] . '</strong>' . $textbf['after'];
            $textbf = self::fromAtoB('\textbf{', '}', $t);
        }

        // \textcolor{color}{texto} → <span style="color:..."> (cuenta llaves anidadas en texto)
        // Soporta también la variante \textcolor[modelo]{spec}{texto} (e.g. [HTML]{FF0000}).
        while (true) {
            $pos = strpos($t, '\textcolor');
            if ($pos === false) break;
            $cursor = $pos + strlen('\textcolor');

            // Saltar [modelo] opcional
            if (substr($t, $cursor, 1) === '[') {
                $closeBracket = strpos($t, ']', $cursor);
                if ($closeBracket === false) break;
                $cursor = $closeBracket + 1;
            }

            // Primer argumento (color o spec)
            if (substr($t, $cursor, 1) !== '{') break;
            $cursor++;
            $colorEnd = strpos($t, '}', $cursor);
            if ($colorEnd === false) break;
            $color = trim(substr($t, $cursor, $colorEnd - $cursor));
            $cursor = $colorEnd + 1;

            // Segundo argumento (texto, con llaves anidadas)
            if (substr($t, $cursor, 1) !== '{') break;
            $cursor++;
            $braceCount = 1;
            $textStart = $cursor;
            $len = strlen($t);
            while ($cursor < $len && $braceCount > 0) {
                $ch = $t[$cursor];
                // Saltar caracteres precedidos por backslash
                if ($cursor > $textStart && $t[$cursor - 1] === '\\') {
                    $cursor++;
                    continue;
                }
                if ($ch === '{')      $braceCount++;
                elseif ($ch === '}')  $braceCount--;
                if ($braceCount > 0)  $cursor++;
            }
            if ($braceCount !== 0) break; // sin cerrar
            $text = substr($t, $textStart, $cursor - $textStart);
            $after = substr($t, $cursor + 1);

            // Sanitizar color: solo caracteres seguros para CSS
            $cssColor = preg_replace('/[^a-zA-Z0-9#,.% ]/', '', $color);
            if ($cssColor === '') {
                // Si no es válido, dejar el texto sin color
                $t = substr($t, 0, $pos) . $text . $after;
                continue;
            }

            $t = substr($t, 0, $pos)
               . '<span style="color:' . $cssColor . ';">'
               . $text
               . '</span>'
               . $after;
        }

        // \boxed{} en modo texto → recuadro (cuenta llaves anidadas; en math, MathJax lo maneja)
        $boxed = self::extractBracedContent('\boxed', $t);
        while ($boxed['inside'] !== '') {
            $t = $boxed['before']
               . '<span style="display:inline-block; border:1px solid #333; padding:1px 6px; border-radius:3px;">'
               . $boxed['inside']
               . '</span>'
               . $boxed['after'];
            $boxed = self::extractBracedContent('\boxed', $t);
        }

        // \text{} en modo texto (típico dentro de \boxed) → desenvolver, conservar contenido
        $text = self::fromAtoB('\text{', '}', $t);
        while ($text['inside'] != '') {
            $t = $text['before'] . $text['inside'] . $text['after'];
            $text = self::fromAtoB('\text{', '}', $t);
        }

        // \textit{} → <em>
        $textit = self::fromAtoB('\textit{', '}', $t);
        while ($textit['inside'] != '') {
            $t = $textit['before'] . '<em>' . $textit['inside'] . '</em>' . $textit['after'];
            $textit = self::fromAtoB('\textit{', '}', $t);
        }

        // \textrm{} → mantener contenido (roman = fuente normal)
        $textrm = self::fromAtoB('\textrm{', '}', $t);
        while ($textrm['inside'] != '') {
            $t = $textrm['before'] . $textrm['inside'] . $textrm['after'];
            $textrm = self::fromAtoB('\textrm{', '}', $t);
        }

        // \texttt{} → <code>
        $texttt = self::fromAtoB('\texttt{', '}', $t);
        while ($texttt['inside'] != '') {
            $t = $texttt['before'] . '<code>' . $texttt['inside'] . '</code>' . $texttt['after'];
            $texttt = self::fromAtoB('\texttt{', '}', $t);
        }

        // \textsf{} → mantener contenido (sin serif)
        $textsf = self::fromAtoB('\textsf{', '}', $t);
        while ($textsf['inside'] != '') {
            $t = $textsf['before'] . $textsf['inside'] . $textsf['after'];
            $textsf = self::fromAtoB('\textsf{', '}', $t);
        }

        // \underline{} → <u>
        $underline = self::fromAtoB('\underline{', '}', $t);
        while ($underline['inside'] != '') {
            $t = $underline['before'] . '<u>' . $underline['inside'] . '</u>' . $underline['after'];
            $underline = self::fromAtoB('\underline{', '}', $t);
        }

        // Eliminar \textnormal{} pero mantener contenido
        $textnormal = self::fromAtoB('\textnormal{', '}', $t);
        while ($textnormal['inside'] != '') {
            $t = $textnormal['before'] . $textnormal['inside'] . $textnormal['after'];
            $textnormal = self::fromAtoB('\textnormal{', '}', $t);
        }

        // Eliminar \ensuremath{} pero mantener contenido
        $ensuremath = self::fromAtoB('\ensuremath{', '}', $t);
        while ($ensuremath['inside'] != '') {
            $t = $ensuremath['before'] . $ensuremath['inside'] . $ensuremath['after'];
            $ensuremath = self::fromAtoB('\ensuremath{', '}', $t);
        }

    // Procesar \includegraphics ANTES de center para evitar conflictos
// Con opciones: \includegraphics[...]{...} (permite espacios antes de [ y alrededor de =)
$t = preg_replace_callback(
    '/\\\\includegraphics\s*\[(.*?)\]\s*\{(.*?)\}/',
    function($matches) {
        return self::getIm($matches[0]);
    },
    $t
);

// Sin opciones: \includegraphics{...}
$t = preg_replace_callback(
    '/\\\\includegraphics\{([^}]+)\}/',
    function($matches) {
        return self::getImSimple($matches[1]);
    },
    $t
);

        // center environment — use regex to only wrap matched pairs, avoiding unclosed divs
        $t = preg_replace(
            '/\\\\begin\{center\}(.*?)\\\\end\{center\}/s',
            '<div style="display:flex; justify-content:center">$1</div>',
            $t
        );
        // Remove any unmatched \begin{center} or \end{center} that remain
        $t = str_replace(['\begin{center}', '\end{center}'], '', $t);

        // (TikZ ya fue procesado al inicio con placeholders)

// Eliminar \definecolor sueltos
$t = preg_replace('/\\\\definecolor\{[^}]+\}\{[^}]+\}\{[^}]+\}/', '', $t);
// section* (como h2)
        $section = self::fromAtoB('\section*{', '}', $t);
        while ($section['inside'] != '') {
            $t = $section['before'] . '<h2>' . $section['inside'] . '</h2>' . $section['after'];
            $section = self::fromAtoB('\section*{', '}', $t);
        }

        // section sin asterisco (como h2)
        $section = self::fromAtoB('\section{', '}', $t);
        while ($section['inside'] != '') {
            $t = $section['before'] . '<h2>' . $section['inside'] . '</h2>' . $section['after'];
            $section = self::fromAtoB('\section{', '}', $t);
        }

        // subsection* (como h3)
        $subsection = self::fromAtoB('\subsection*{', '}', $t);
        while ($subsection['inside'] != '') {
            $t = $subsection['before'] . '<h3>' . $subsection['inside'] . '</h3>' . $subsection['after'];
            $subsection = self::fromAtoB('\subsection*{', '}', $t);
        }

        // subsection sin asterisco (como h3)
        $subsection = self::fromAtoB('\subsection{', '}', $t);
        while ($subsection['inside'] != '') {
            $t = $subsection['before'] . '<h3>' . $subsection['inside'] . '</h3>' . $subsection['after'];
            $subsection = self::fromAtoB('\subsection{', '}', $t);
        }

        // paragraph
        // paragraph con asterisco
        $par = self::fromAtoB('\paragraph*{', '}', $t);
        while ($par['inside'] != '') {
            $t = $par['before'] . '<b>' . $par['inside'] . '</b>' . $par['after'];
            $par = self::fromAtoB('\paragraph*{', '}', $t);
        }

        // paragraph sin asterisco
        $par = self::fromAtoB('\paragraph{', '}', $t);
        while ($par['inside'] != '') {
            $t = $par['before'] . '<b>' . $par['inside'] . '</b>' . $par['after'];
            $par = self::fromAtoB('\paragraph{', '}', $t);
        }

        // subparagraph
        $subpar = self::fromAtoB('\subparagraph{', '}', $t);
        while ($subpar['inside'] != '') {
            $t = $subpar['before'] . '<b>' . $subpar['inside'] . '</b>' . $subpar['after'];
            $subpar = self::fromAtoB('\subparagraph{', '}', $t);
        }


        $t = preg_replace('/\\\\par\b/', '<br>', $t);  // \par → <br>, pero NO \parbox
        $t = str_replace('*\;', '*', $t);

        // Solución
        $t = str_replace('\begin{sol}', '<br><i>Solución</i>: ', $t);
        $t = str_replace('\end{sol}', '<br>', $t);

        // Signos de interrogación/exclamación invertidos
        $t = str_replace('?`',  '¿', $t);
        $t = str_replace("?'",  '¿', $t);
        $t = str_replace('!`',  '¡', $t);
        $t = str_replace("!'",  '¡', $t);

        // Acentos agudos con llaves: \'{vocal}  (antes que sin llaves)
        $t = str_replace("\\'{a}", 'á', $t);  $t = str_replace("\\'{A}", 'Á', $t);
        $t = str_replace("\\'{e}", 'é', $t);  $t = str_replace("\\'{E}", 'É', $t);
        $t = str_replace("\\'{i}", 'í', $t);  $t = str_replace("\\'{I}", 'Í', $t);
        $t = str_replace("\\'{o}", 'ó', $t);  $t = str_replace("\\'{O}", 'Ó', $t);
        $t = str_replace("\\'{u}", 'ú', $t);  $t = str_replace("\\'{U}", 'Ú', $t);

        // Acentos agudos sin llaves: \'vocal
        $t = str_replace("\'a", 'á', $t);  $t = str_replace("\'A", 'Á', $t);
        $t = str_replace("\'e", 'é', $t);  $t = str_replace("\'E", 'É', $t);
        $t = str_replace("\'i", 'í', $t);  $t = str_replace("\'I", 'Í', $t);
        $t = str_replace("\'o", 'ó', $t);  $t = str_replace("\'O", 'Ó', $t);
        $t = str_replace("\'u", 'ú', $t);  $t = str_replace("\'U", 'Ú', $t);

        // Acentos graves: \`vocal (con y sin llaves)
        $t = str_replace('\`{a}', 'à', $t);  $t = str_replace('\`{A}', 'À', $t);
        $t = str_replace('\`{e}', 'è', $t);  $t = str_replace('\`{E}', 'È', $t);
        $t = str_replace('\`{i}', 'ì', $t);  $t = str_replace('\`{I}', 'Ì', $t);
        $t = str_replace('\`{o}', 'ò', $t);  $t = str_replace('\`{O}', 'Ò', $t);
        $t = str_replace('\`{u}', 'ù', $t);  $t = str_replace('\`{U}', 'Ù', $t);
        $t = str_replace('\`a',   'à', $t);  $t = str_replace('\`A',   'À', $t);
        $t = str_replace('\`e',   'è', $t);  $t = str_replace('\`E',   'È', $t);
        $t = str_replace('\`i',   'ì', $t);  $t = str_replace('\`I',   'Ì', $t);
        $t = str_replace('\`o',   'ò', $t);  $t = str_replace('\`O',   'Ò', $t);
        $t = str_replace('\`u',   'ù', $t);  $t = str_replace('\`U',   'Ù', $t);

        // Circunflejo: \^vocal (con y sin llaves)
        $t = str_replace('\^{a}', 'â', $t);  $t = str_replace('\^{A}', 'Â', $t);
        $t = str_replace('\^{e}', 'ê', $t);  $t = str_replace('\^{E}', 'Ê', $t);
        $t = str_replace('\^{i}', 'î', $t);  $t = str_replace('\^{I}', 'Î', $t);
        $t = str_replace('\^{o}', 'ô', $t);  $t = str_replace('\^{O}', 'Ô', $t);
        $t = str_replace('\^{u}', 'û', $t);  $t = str_replace('\^{U}', 'Û', $t);
        $t = str_replace('\^a',   'â', $t);  $t = str_replace('\^A',   'Â', $t);
        $t = str_replace('\^e',   'ê', $t);  $t = str_replace('\^E',   'Ê', $t);
        $t = str_replace('\^i',   'î', $t);  $t = str_replace('\^I',   'Î', $t);
        $t = str_replace('\^o',   'ô', $t);  $t = str_replace('\^O',   'Ô', $t);
        $t = str_replace('\^u',   'û', $t);  $t = str_replace('\^U',   'Û', $t);


        // Diéresis: \"vocal (con y sin llaves)
        $t = str_replace('\"{a}', 'ä', $t);  $t = str_replace('\"{A}', 'Ä', $t);
        $t = str_replace('\"{e}', 'ë', $t);  $t = str_replace('\"{E}', 'Ë', $t);
        $t = str_replace('\"{i}', 'ï', $t);  $t = str_replace('\"{I}', 'Ï', $t);
        $t = str_replace('\"{o}', 'ö', $t);  $t = str_replace('\"{O}', 'Ö', $t);
        $t = str_replace('\"{u}', 'ü', $t);  $t = str_replace('\"{U}', 'Ü', $t);
        $t = str_replace('\"a',   'ä', $t);  $t = str_replace('\"A',   'Ä', $t);
        $t = str_replace('\"e',   'ë', $t);  $t = str_replace('\"E',   'Ë', $t);
        $t = str_replace('\"i',   'ï', $t);  $t = str_replace('\"I',   'Ï', $t);
        $t = str_replace('\"o',   'ö', $t);  $t = str_replace('\"O',   'Ö', $t);
        $t = str_replace('\"u',   'ü', $t);  $t = str_replace('\"U',   'Ü', $t);

        // Cedilla
        $t = str_replace('\c{c}', 'ç', $t);  $t = str_replace('\c{C}', 'Ç', $t);

        // Otros (símbolos no españoles que sí pueden aparecer en textos académicos)
        $t = preg_replace('/\\\\ss(?![a-zA-Z])/', 'ß', $t);
        $t = preg_replace('/\\\\ae(?![a-zA-Z])/', 'æ', $t);  $t = preg_replace('/\\\\AE(?![a-zA-Z])/', 'Æ', $t);
        $t = preg_replace('/\\\\oe(?![a-zA-Z])/', 'œ', $t);  $t = preg_replace('/\\\\OE(?![a-zA-Z])/', 'Œ', $t);
        // \o y \O (ø, Ø) eliminados: no se usan en español y causan conflicto con \overrightarrow, \overleftarrow, etc.


        // Proof
        $t = str_replace('\begin{proof}[Solución]', '<br><i>Solución</i>: ', $t);
        $t = str_replace('\begin{proof}[Demostración]', '<br><i>Demostración</i>: ', $t);
        $t = str_replace('\begin{proof}[Proof]', '<br><i>Proof</i>: ', $t);
        $t = str_replace('\begin{proof}', '<br><i>Demostración</i>: ', $t);
        $t = str_replace('\end{proof}', ' &#9634; <br>', $t);

        // Estilos para Retos
        $style_reto = 'background: #f8f9fa; border-left: 4px solid #6366f1; padding: 1rem; margin: 1rem 0; border-radius: 4px;';
        $style_reto_resuelto = 'background: #f0fdf4; border-left: 4px solid #22c55e; padding: 1rem; margin: 1rem 0; border-radius: 4px;';
        $style_solution = 'background: #fffbeb; border-left: 3px solid #f59e0b; padding: 0.75rem; margin: 0.5rem 0; border-radius: 4px;';

        // \exercise{...} - Convertir a Reto o Reto resuelto según contexto (con llaves anidadas)
        $exercise = self::extractBracedContent('\exercise', $t);
        while ($exercise['inside'] != '') {
            if (self::$isPreambleContext) {
                self::$countRetoResuelto++;
                $retoHtml = '<div style="' . $style_reto_resuelto . '"><strong>Reto resuelto ' . self::$countRetoResuelto . ':</strong><br>' . $exercise['inside'] . '</div>';
            } else {
                self::$countReto++;
                $retoHtml = '<div style="' . $style_reto . '"><strong>Reto ' . self::$countReto . ':</strong><br>' . $exercise['inside'] . '</div>';
            }
            $t = $exercise['before'] . $retoHtml . $exercise['after'];
            $exercise = self::extractBracedContent('\exercise', $t);
        }

        // \solution{...} - Convertir a bloque de solución (con llaves anidadas)
        $solution = self::extractBracedContent('\solution', $t);
        while ($solution['inside'] != '') {
            $solutionHtml = '<div style="' . $style_solution . '"><strong><em>Solución:</em></strong><br>' . $solution['inside'] . '</div>';
            $t = $solution['before'] . $solutionHtml . $solution['after'];
            $solution = self::extractBracedContent('\solution', $t);
        }

        // Entorno ejer - Convertir a Reto o Reto resuelto según contexto
        $ejer = self::fromAtoB('\begin{ejer}', '\end{ejer}', $t);
        while ($ejer['inside'] != '') {
            if (self::$isPreambleContext) {
                self::$countRetoResuelto++;
                $ejerHtml = '<div style="' . $style_reto_resuelto . '"><strong>Reto resuelto ' . self::$countRetoResuelto . ':</strong><br>' . $ejer['inside'] . '</div>';
            } else {
                self::$countReto++;
                $ejerHtml = '<div style="' . $style_reto . '"><strong>Reto ' . self::$countReto . ':</strong><br>' . $ejer['inside'] . '</div>';
            }
            $t = $ejer['before'] . $ejerHtml . $ejer['after'];
            $ejer = self::fromAtoB('\begin{ejer}', '\end{ejer}', $t);
        }

        // Entorno tcolorbox - Eliminar el entorno pero mantener contenido con marco simple
        $tcolorbox = self::fromAtoB('\begin{tcolorbox}', '\end{tcolorbox}', $t);
        while ($tcolorbox['inside'] != '') {
            // Eliminar posibles opciones [...]  al inicio del contenido
            $content = preg_replace('/^\s*\[[^\]]*\]\s*/', '', $tcolorbox['inside']);
            $boxHtml = '<div style="border: 1px solid #cbd5e1; padding: 1rem; margin: 0.5rem 0; border-radius: 4px; background: #f8fafc;">' . $content . '</div>';
            $t = $tcolorbox['before'] . $boxHtml . $tcolorbox['after'];
            $tcolorbox = self::fromAtoB('\begin{tcolorbox}', '\end{tcolorbox}', $t);
        }

        // Entorno definition - Con numeración como los ejemplos
        $style_definition = 'border-left: 3px solid #3b82f6; padding-left: 15px; margin: 1rem 0; background: #eff6ff; padding: 1rem; border-radius: 4px;';
        $definition = self::fromAtoB('\begin{definition}', '\end{definition}', $t);
        while ($definition['inside'] != '') {
            self::$countDefinition++;
            $defHtml = '<div style="' . $style_definition . '"><strong>Definición ' . self::$countDefinition . ':</strong> ' . $definition['inside'] . '</div>';
            $t = $definition['before'] . $defHtml . $definition['after'];
            $definition = self::fromAtoB('\begin{definition}', '\end{definition}', $t);
        }

        // Ejemplos
        $needle = '\begin{ejem}';
        $pos = strpos($t, $needle);
        while ($pos !== false) {
            self::$countExemple++;
            $replace = '<blockquote style="' . $style_ex . '"><b>Ejemplo ' . self::$countExemple . ': </b>';
            $t = substr_replace($t, $replace, $pos, strlen($needle));
            $pos = strpos($t, $needle);
        }
        $t = str_replace('\end{ejem}', '</blockquote>', $t);

        // Teoremas
        $needle = '\begin{theorem}';
        $pos = strpos($t, $needle);
        while ($pos !== false) {
            self::$countTheorem++;
            $replace = '<p style="' . $style_th . '"><b>Teorema ' . self::$countTheorem . ': </b>';
            $t = substr_replace($t, $replace, $pos, strlen($needle));
            $pos = strpos($t, $needle);
        }
        $t = str_replace('\end{theorem}', '</p>', $t);

        // Lemas (lema / lemma)
        foreach (['\begin{lema}', '\begin{lemma}'] as $needle) {
            $pos = strpos($t, $needle);
            while ($pos !== false) {
                self::$countLemma++;
                $replace = '<p style="' . $style_th . '"><b>Lema ' . self::$countLemma . ': </b>';
                $t = substr_replace($t, $replace, $pos, strlen($needle));
                $pos = strpos($t, $needle);
            }
        }
        $t = str_replace('\end{lema}', '</p>', $t);
        $t = str_replace('\end{lemma}', '</p>', $t);

        // Definiciones
        $needle = '\begin{defin}';
        $pos = strpos($t, $needle);
        while ($pos !== false) {
            self::$countDefinition++;
            $replace = '<p style="' . $style_def . '"><b>Definición ' . self::$countDefinition . ': </b>';
            $t = substr_replace($t, $replace, $pos, strlen($needle));
            $pos = strpos($t, $needle);
        }
        $t = str_replace('\end{defin}', '</p>', $t);

        // Italic
        $bold = self::fromAtoB('\textit{', '}', $t);
        while ($bold['inside'] != '') {
            $t = $bold['before'] . '<i>' . $bold['inside'] . '</i>' . $bold['after'];
            $bold = self::fromAtoB('\textit{', '}', $t);
        }

        // Bold
        $bold = self::fromAtoB('\textbf{', '}', $t);
        while ($bold['inside'] != '') {
            $t = $bold['before'] . '<b>' . $bold['inside'] . '</b>' . $bold['after'];
            $bold = self::fromAtoB('\textbf{', '}', $t);
        }

           // Bold 2
        $bold = self::fromAtoB('{\bf', '}', $t);
        while ($bold['inside'] != '') {
            $t = $bold['before'] . '<b>' . $bold['inside'] . '</b>' . $bold['after'];
            $bold = self::fromAtoB('{\bf', '}', $t);
        }

        // Italic 2 ({\it ...})
        $it = self::fromAtoB('{\it', '}', $t);
        while ($it['inside'] != '') {
            $t = $it['before'] . '<i>' . $it['inside'] . '</i>' . $it['after'];
            $it = self::fromAtoB('{\it', '}', $t);
        }

        // Monospace / código
        $tt = self::fromAtoB('\texttt{', '}', $t);
        while ($tt['inside'] != '') {
            $t = $tt['before'] . '<code>' . $tt['inside'] . '</code>' . $tt['after'];
            $tt = self::fromAtoB('\texttt{', '}', $t);
        }

        // Subrayado
        $underline = self::fromAtoB('\underline{', '}', $t);
        while ($underline['inside'] != '') {
            $t = $underline['before'] . '<u>' . $underline['inside'] . '</u>' . $underline['after'];
            $underline = self::fromAtoB('\underline{', '}', $t);
        }

        // Small caps (simulado con estilo CSS)
        $sc = self::fromAtoB('\textsc{', '}', $t);
        while ($sc['inside'] != '') {
            $t = $sc['before'] . '<span style="font-variant: small-caps;">' . $sc['inside'] . '</span>' . $sc['after'];
            $sc = self::fromAtoB('\textsc{', '}', $t);
        }

        // \qed y \qedhere → cuadrado de fin de demostración
        $t = str_replace(['\qedhere', '\qed'], '<span style="float:right;">&#9632;</span>', $t);

        // Rules
        $rule = self::fromAtoB('\*rule[', ']', $t);
        while ($rule['inside'] != '') {
            $w = self::fromAtoB('{', '\textwidth}', $rule['after']);
            $wid = intval(floatval($w['inside']) * 100);
            $grosor = self::fromAtoB('{', 'pt}', $w['after']);
            $t = $rule['before'] . '<div style="width:' . $wid . '%; border-top:1px solid red"></div>' . $grosor['after'];
            $rule = self::fromAtoB('\*rule[', ']', $t);
        }

        // \pref{N} → <a href="/problemas/N">Problema N</a>
        $t = preg_replace_callback(
            '/\\\\pref\{(\d+)\}/',
            fn($m) => '<a href="' . url('/problemas/' . $m[1]) . '">Problema ' . $m[1] . '</a>',
            $t
        );

        // \href{url}{texto} → <a href="url">texto</a>
        $href = self::fromAtoB('\href{', '}', $t);
        while ($href['inside'] != '') {
            $temp = $href['before'] . '<a href="' . $href['inside'] . '">';
            $temp2 = self::fromAtoB('{', '}', $href['after']);
            $t = $temp . $temp2['inside'] . '</a>' . $temp2['after'];
            $href = self::fromAtoB('\href{', '}', $t);
        }

        // \url{url} → <a href="url">url</a>
        $url = self::fromAtoB('\url{', '}', $t);
        while ($url['inside'] != '') {
            $u = $url['inside'];
            $t = $url['before'] . '<a href="' . $u . '">' . $u . '</a>' . $url['after'];
            $url = self::fromAtoB('\url{', '}', $t);
        }

        // Color
        $color = self::fromAtoB('\color{', '}', $t);
        while ($color['inside'] != '') {
            $colName = $color['inside'];
            $spacePos = strpos($color['after'], ' ');
            $colText = substr($color['after'], 0, $spacePos);
            $afterText = substr($color['after'], $spacePos);
            $t = $color['before'] . '<span style="color: ' . $colName . '">' . $colText . '</span>' . $afterText;
            $color = self::fromAtoB('\color{', '}', $t);
        }

        // Listas
        // Listas enumerate
$li = self::fromAtoB('\begin{enumerate}', '\end{enumerate}', $t);
while ($li['inside'] != '') {
    // Reemplazar \item[X] y \item [X] (con o sin espacio) por <li>
    $l = preg_replace('/\\\\item\s*\[[^\]]*\]/', '<li>', $li['inside']);
    // Reemplazar \item sin corchetes
    $l = str_replace('\item', '<li>', $l);
    $t = $li['before'] . '<ol type="a">' . $l . '</ol>' . $li['after'];
    $li = self::fromAtoB('\begin{enumerate}', '\end{enumerate}', $t);
}

// Listas itemize (viñetas)
$li = self::fromAtoB('\begin{itemize}', '\end{itemize}', $t);
while ($li['inside'] != '') {
    // Reemplazar \item[X] y \item [X] (con o sin espacio) por <li>
    $l = preg_replace('/\\\\item\s*\[[^\]]*\]/', '<li>', $li['inside']);
    // Reemplazar \item sin corchetes
    $l = str_replace('\item', '<li>', $l);
    $t = $li['before'] . '<ul>' . $l . '</ul>' . $li['after'];
    $li = self::fromAtoB('\begin{itemize}', '\end{itemize}', $t);
}

// Procesar \fbox{...} antes de eliminar minipages
$t = self::processConsecutiveFboxes($t);

// Entorno minipage (eliminar el entorno pero mantener contenido)
$t = preg_replace('/\\\\begin\{minipage\}(\[[^\]]*\])*\{[^}]*\}/', '', $t);
$t = str_replace('\end{minipage}', '', $t);

// Entornos de alineación que eliminamos pero mantenemos contenido
$t = str_replace('\begin{flushleft}', '<div style="text-align: left;">', $t);
$t = str_replace('\end{flushleft}', '</div>', $t);
$t = str_replace('\begin{flushright}', '<div style="text-align: right;">', $t);
$t = str_replace('\end{flushright}', '</div>', $t);

// \subsubsection{...} → <h4>
$sub3 = self::fromAtoB('\subsubsection{', '}', $t);
while ($sub3['inside'] != '') {
    $t = $sub3['before'] . '<h4 style="margin:1rem 0 0.4rem; font-size:1rem; font-weight:700; color:#2d3748;">' . $sub3['inside'] . '</h4>' . $sub3['after'];
    $sub3 = self::fromAtoB('\subsubsection{', '}', $t);
}

// \begin{multicols}{N}...\end{multicols} → flex columns con distribución equitativa
$t = preg_replace_callback(
    '/\\\\begin\{multicols\}\{(\d+)\}([\s\S]*?)\\\\end\{multicols\}/s',
    function ($m) {
        $cols = max(1, (int) $m[1]);
        $content = trim($m[2]);

        // Si el contenido es una lista (ol/ul), distribuir ítems equitativamente
        if (preg_match('/^<(ol|ul)([^>]*)>([\s\S]*)<\/\1>\s*$/s', $content, $lm)) {
            $tag     = $lm[1];
            $attrs   = $lm[2];  // p.ej. ' type="a"'
            $inner   = $lm[3];

            // Los <li> no tienen </li>, separar por <li>
            $parts = preg_split('/<li>/', $inner);
            $items = array_values(array_filter(array_map('trim', $parts)));
            $count = count($items);

            if ($count > 0) {
                $perCol = (int) ceil($count / $cols);
                $html = '<div style="display:flex; gap:1.5rem; align-items:flex-start;">';
                $startIdx = 0;
                foreach (array_chunk($items, $perCol) as $chunk) {
                    $startAttr = ($tag === 'ol' && $startIdx > 0) ? ' start="' . ($startIdx + 1) . '"' : '';
                    $html .= '<div style="flex:1;"><' . $tag . $attrs . $startAttr . '>';
                    foreach ($chunk as $item) {
                        $html .= '<li>' . $item;
                    }
                    $html .= '</' . $tag . '></div>';
                    $startIdx += count($chunk);
                }
                return $html . '</div>';
            }
        }

        // Fallback para contenido no-lista: CSS column-count
        return '<div style="column-count:' . $cols . '; column-gap:1.5rem;">' . $content . '</div>';
    },
    $t
);

// Citas y quotation
$t = str_replace('\begin{quote}', '<blockquote>', $t);
$t = str_replace('\end{quote}', '</blockquote>', $t);
$t = str_replace('\begin{quotation}', '<blockquote>', $t);
$t = str_replace('\end{quotation}', '</blockquote>', $t);

// Verbatim (código preformateado)
$t = str_replace('\begin{verbatim}', '<pre><code>', $t);
$t = str_replace('\end{verbatim}', '</code></pre>', $t);





        // Imágenes (entorno figure)
        // Eliminar opciones de posicionamiento como [H], [h], [t], [b], [p], [htbp], etc.
        $t = preg_replace('/\\\\begin\{figure\}\s*\[[^\]]*\]/', '\begin{figure}', $t);
        $t = preg_replace('/\\\\begin\{subfigure\}\s*\[[^\]]*\]/', '\begin{subfigure}', $t);

        while (strpos($t, '\begin{figure}') !== false) {
            $im = self::fromAtoB('\begin{figure}', '\end{figure}', $t);
            $image = $im['inside'];
            $count = substr_count($image, '\begin{subfigure}');

            if ($count > 0) {
                $tAdd = '<table style="width:100%"><tr>';
                for ($i = 0; $i < $count; $i++) {
                    $subfig = self::fromAtoB('\begin{subfigure}', '\end{subfigure}', $image);
                    $tAdd .= '<td>' . self::getIm($subfig['inside']) . '</td>';
                    $image = $subfig['after'];
                }
                $tAdd .= '</tr></table>';
            } else {
                // Si el contenido ya tiene <img> (procesado antes), envolverlo en div centrado
                if (strpos($image, '<img') !== false || strpos($image, '<embed') !== false) {
                    // Limpiar \centering y espacios
                    $image = str_replace('\centering', '', $image);
                    $tAdd = '<div style="display:flex; justify-content:center; margin: 1rem 0;">' . trim($image) . '</div>';
                } else {
                    $tAdd = self::getIm($image);
                }
            }
            $t = $im['before'] . $tAdd . $im['after'];
        }

        // Tablas
        do {
            $res        = self::fromAtoB('\begin{tabular}', '\end{tabular}', $t);
            $tableInside = $res['inside'];
            if ($tableInside !== '') {
                $specRes = self::fromAtoB('{', '}', $tableInside);
                $colSpec = $specRes['inside'];
                $body    = $specRes['after'];

                // Parse column alignments and vertical bars from spec
                // e.g. "c|cccc" → 5 columnas centradas, columna 2 con border-left
                $aligns = [];
                $leftBorders = [];   // por columna: true si tiene | antes
                $pendingBar = false;
                $rightBorder = false; // | al final del spec → border-right de última columna
                foreach (str_split($colSpec) as $ch) {
                    if ($ch === '|') {
                        if (count($aligns) === 0) {
                            // | al inicio: la primera columna lleva border-left
                            $pendingBar = true;
                        } else {
                            $pendingBar = true;
                        }
                    } elseif ($ch === 'l' || $ch === 'r' || $ch === 'c') {
                        $aligns[] = $ch === 'l' ? 'left' : ($ch === 'r' ? 'right' : 'center');
                        $leftBorders[] = $pendingBar;
                        $pendingBar = false;
                    }
                }
                if ($pendingBar) {
                    $rightBorder = true; // | tras la última letra
                }

                // Split on LaTeX row separator \\ using chr(92) to avoid regex escaping ambiguity
                $bs = chr(92); // one backslash
                $segments = explode($bs . $bs, $body);
                $dataRows = [];
                $bottomBorderOnLast = false;

                $hlineToken = $bs . 'hline';
                foreach ($segments as $seg) {
                    $hlineCount = substr_count($seg, $hlineToken);
                    $content    = trim(str_replace($hlineToken, '', $seg));
                    // Strip optional spacing argument e.g. [4pt] at start of segment (after \\)
                    $content    = trim(preg_replace('/^\[[^\]]*\]/', '', $content));

                    if ($content === '') {
                        if ($hlineCount > 0 && !empty($dataRows)) {
                            $bottomBorderOnLast = true;
                        }
                        continue;
                    }

                    // \hline in the same segment means top border for this row
                    $borderTop = $hlineCount > 0 ? 'border-top:2px solid #888;' : '';
                    $bottomBorderOnLast = false;

                    $cells     = explode('&', $content);
                    $htmlCells = '';
                    $cellCount = count($cells);
                    foreach ($cells as $i => $cell) {
                        $align       = $aligns[$i] ?? 'left';
                        $borderLeft  = !empty($leftBorders[$i]) ? ' border-left:1px solid #888;' : '';
                        $borderRight = ($rightBorder && $i === $cellCount - 1) ? ' border-right:1px solid #888;' : '';
                        $htmlCells .= '<td style="padding:3px 10px; text-align:' . $align . ';' . $borderLeft . $borderRight . '">' . trim($cell) . '</td>';
                    }
                    $dataRows[] = ['html' => $htmlCells, 'top' => $borderTop, 'bottom' => ''];
                }

                if ($bottomBorderOnLast && !empty($dataRows)) {
                    $dataRows[count($dataRows) - 1]['bottom'] = 'border-bottom:2px solid #888;';
                }

                $rowsHtml = '';
                foreach ($dataRows as $row) {
                    $style = $row['top'] . $row['bottom'];
                    $rowsHtml .= '<tr' . ($style ? ' style="' . $style . '"' : '') . '>' . $row['html'] . '</tr>';
                }

                $tableHtml = '<table style="border-collapse:collapse; margin:0.5em auto;">' . $rowsHtml . '</table>';
                $t = $res['before'] . $tableHtml . $res['after'];
            }
        } while ($tableInside !== '');


        

  // Saltos de línea simples → espacio (comportamiento estándar LaTeX)
    $t = str_replace("\n", " ", $t);
    // Restaurar los saltos de párrafo marcados al inicio
    $t = str_replace('###PARABREAK###', '<br><br>', $t);

    // Envolver en párrafo si no está vacío
    if (trim($t) !== '') {
        $t = '<p>' . $t . '</p>';
    }

    // Limpiar párrafos vacíos que puedan haberse creado
    $t = preg_replace('/<p[^>]*>\s*<\/p>/', '', $t);

$t = str_replace('&&&LT&&&', '&lt;', $t);
    $t = str_replace('&&&GT&&&', '&gt;', $t);
$t = str_replace( 'PCTG','\%', $t);

// \degree fuera de math → símbolo °
$t = str_replace('\degree', '°', $t);

// Restaurar bloques math escapando < y > para HTML (MathJax los entiende como &lt;/&gt;)
foreach (self::$mathBlocks as $placeholder => $mathContent) {
    $t = str_replace($placeholder, str_replace(['<', '>'], ['&lt;', '&gt;'], $mathContent), $t);
}

// Restaurar bloques TikZ (que fueron protegidos al inicio)
foreach (self::$tikzBlocks as $placeholder => $tikzHtml) {
    $t = str_replace($placeholder, $tikzHtml, $t);
}

$t .= self::getDebugScript();
        return $t;
    }

    public static function resetCounters()
    {
        self::$countDefinition = 0;
        self::$countExemple = 0;
        self::$countTheorem = 0;
        self::$countLemma = 0;
        self::$countReto = 0;
        self::$countRetoResuelto = 0;
    }

    /**
     * Establecer contexto de preámbulo (para numeración de Retos)
     */
    public static function setPreambleContext($isPreamble = true)
    {
        self::$isPreambleContext = $isPreamble;
    }

    /**
     * Procesar HTML para preámbulo (usa "Reto resuelto")
     */
    public static function toHtmlPreamble($t)
    {
        self::$isPreambleContext = true;
        $result = self::toHtml($t);
        self::$isPreambleContext = false;
        return $result;
    }
}