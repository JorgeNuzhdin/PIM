<?php

namespace App\Helpers;

class LatexHelper
{
    private static $countDefinition = 0;
    private static $countExemple = 0;
    private static $countTheorem = 0;
    private static $debugMessages = [];  
    private static function debug($message, $data = null)
    {
        $debugInfo = $message;
        if ($data !== null) {
            $debugInfo .= ': ' . json_encode($data);
        }
        self::$debugMessages[] = $debugInfo;
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

 
private static function getIm($image)
{
      
    $pattern = '/\\\\includegraphics\[(.*?)\]\{(.*?)\}/';
    if (preg_match($pattern, $image, $matches)) {
         
        $options = $matches[1];
        $filename = $matches[2];
        
        // Determinar el ancho
        $width = 100;
        
        if (preg_match('/width=([\d.]+)/', $options, $widthMatch)) {
            $width = floatval($widthMatch[1]) * 100;
        }
        elseif (preg_match('/scale=([\d.]+)/', $options, $scaleMatch)) {
            $width = floatval($scaleMatch[1]) * 100;
          }
        
        $imName = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $filename);
        $imName = trim($imName);
        
        
        $figure = \App\Models\Figure::where('title', $filename)->first();
        
        if (!$figure) {
            $figure = \App\Models\Figure::where('title', $imName)->first();
        }
        
        if (!$figure && !str_ends_with($filename, '.pdf')) {
            $figure = \App\Models\Figure::where('title', $imName . '.pdf')->first();
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
    
    $figure = \App\Models\Figure::where('title', $filename)->first();
    
    if (!$figure) {
        $figure = \App\Models\Figure::where('title', $imName)->first();
    }
    
    if (!$figure && !str_ends_with($filename, '.pdf')) {
        $figure = \App\Models\Figure::where('title', $imName . '.pdf')->first();
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


    public static function toHtml($t)
    {
        if ($t == '') return '';

        $style = "border: 1px solid; text-align: center; vertical-align: center";
        $style_def = "border-left:solid #000; padding-left:15px; margin-left:10px;";
        $style_th = "border:solid #000; padding:10px; margin:10px;";
        $style_ex = 'border-left: 2px solid red; padding-left:15px; margin-left:10px;';

          $t = str_replace(['u000au000au000au000a', 'u000au000au000a', 'u000au000a', 'u000a', 'u000d', 'u0009'], ["\n\n", "\n\n", "\n\n", "\n", "\r", "\t"], $t);
    $t = str_replace(['\u000a\u000a\u000a\u000a', '\u000a\u000a\u000a', '\u000a\u000a', '\u000a', '\u000d', '\u0009'], ["\n\n", "\n\n", "\n\n", "\n", "\r", "\t"], $t);
    
    // Eliminar múltiples saltos de línea consecutivos
    $t = preg_replace("/\n{3,}/", "\n\n", $t);

     $t = str_replace('\%', 'PCTG', $t);
         $t = preg_replace('/^%.*$/m', '', $t);
       $t = str_replace('<', '&&&LT&&&', $t);
    $t = str_replace('>', '&&&GT&&&', $t);

      
    // Procesar \includegraphics ANTES de center para evitar conflictos
// Con opciones: \includegraphics[...]{...}
$t = preg_replace_callback(
    '/\\\\includegraphics\[(.*?)\]\{(.*?)\}/',
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

//align
$t=str_replace('\begin{center}', '<div style="display:flex; justify-content:center">', $t);
$t=str_replace('\end{center}', '</div>', $t);
       

        // Align
        $t = str_replace('\begin{center}', '<div style="display:flex; justify-content:center">', $t);
        $t = str_replace('\end{center}', '</div>', $t);

        //dibujos
$t=str_replace('\begin{tikzpicture}', '<div class="tikz" style="display:flex; justify-content:center"><script type="text/tikz">\begin{tikzpicture}', $t);
$t=str_replace('\end{tikzpicture}', '\end{tikzpicture}</script></div>', $t);

// Eliminar \definecolor sueltos
$t = preg_replace('/\\\\definecolor\{[^}]+\}\{[^}]+\}\{[^}]+\}/', '', $t);
// paragraph
        $par = self::fromAtoB('\paragraph*{', '}', $t);
        while ($par['inside'] != '') {
            $t = $par['before'] . '<b>' . $par['inside'] . '</b>' . $par['after'];
            $par = self::fromAtoB('\paragraph*{', '}', $t);
        }


        $t = str_replace('\par', '<br>', $t);
        $t = str_replace('*\;', '*', $t);

        // Solución
        $t = str_replace('\begin{sol}', '<br><i>Solución</i>: ', $t);
        $t = str_replace('\end{sol}', '<br>', $t);

//tildes
        $t = str_replace("\'e", 'é', $t);
         $t = str_replace("\'a", 'á', $t);
          $t = str_replace("\'o", 'ó', $t);
           $t = str_replace("\'u", 'ú', $t); $t = str_replace("\'i", 'í', $t);


        // Proof
        $t = str_replace('\begin{proof}[Solución]', '<br><i>Solución</i>: ', $t);
        $t = str_replace('\begin{proof}[Demostración]', '<br><i>Demostración</i>: ', $t);
        $t = str_replace('\end{proof}', ' &#9634; <br>', $t);

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

        // Rules
        $rule = self::fromAtoB('\*rule[', ']', $t);
        while ($rule['inside'] != '') {
            $w = self::fromAtoB('{', '\textwidth}', $rule['after']);
            $wid = intval(floatval($w['inside']) * 100);
            $grosor = self::fromAtoB('{', 'pt}', $w['after']);
            $t = $rule['before'] . '<div style="width:' . $wid . '%; border-top:1px solid red"></div>' . $grosor['after'];
            $rule = self::fromAtoB('\*rule[', ']', $t);
        }

        // Href
        $href = self::fromAtoB('\href{', '}', $t);
        while ($href['inside'] != '') {
            $temp = $href['before'] . '<a href="' . $href['inside'] . '">';
            $temp2 = self::fromAtoB('{', '}', $href['after']);
            $t = $temp . $temp2['inside'] . '</a>' . $temp2['after'];
            $href = self::fromAtoB('\href{', '}', $t);
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





        // Imágenes
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
                $tAdd = self::getIm($image);
            }
            $t = $im['before'] . $tAdd . $im['after'];
        }

        // Tablas
        do {
            $res = self::fromAtoB('\begin{tabular}', '\end{tabular}', $t);
            $table1 = $res['inside'];
            if ($table1 != '') {
                $table2 = self::fromAtoB('{', '}', $table1);
                $table = $table2['after'];
                $table = str_replace('\hline', '', $table);
                $table = str_replace('&', "</td><td style='$style'>", $table);
                $lastslash = strrpos($table, '\\');
                $afterSlash = trim(substr($table, $lastslash + 1));
                if ($afterSlash == '') {
                    $table = substr($table, 0, $lastslash - 1);
                }
                $table = str_replace('\\\\', "</tr><tr><td style='$style'>", $table);
                $table = '<table class="centered" style="width:30%;"><tr><td style="' . $style . '">' . $table . '</td></tr></table>';
                $t = $res['before'] . $table . $res['after'];
            }
        } while ($table1 != '');


        

  // Convertir saltos de línea dobles en párrafos y simples en <br>
    $t = preg_replace("/\n\n+/", "</p><p>", $t);
    $t = str_replace("\n", "<br>\n", $t);
    
    // Envolver en párrafo si no está vacío
    if (trim($t) !== '') {
        $t = '<p>' . $t . '</p>';
    }
    
    // Limpiar párrafos vacíos que puedan haberse creado
    $t = preg_replace('/<p>\s*<\/p>/', '', $t);

$t = str_replace('&&&LT&&&', '&lt;', $t);
    $t = str_replace('&&&GT&&&', '&gt;', $t);
$t = str_replace( 'PCTG','\%', $t);

$t .= self::getDebugScript();
        return $t;
    }

    public static function resetCounters()
    {
        self::$countDefinition = 0;
        self::$countExemple = 0;
        self::$countTheorem = 0;
    }
}