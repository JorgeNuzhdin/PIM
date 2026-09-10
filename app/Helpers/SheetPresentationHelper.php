<?php

namespace App\Helpers;

/**
 * Convierte una hoja (preámbulo + problemas) en una secuencia de páginas
 * para la vista de presentación HTML (pim_sheets.html).
 *
 * Reglas:
 *  - El preámbulo se corta por secciones: cada \section*{} / \subsection*{} abre
 *    una página de explicación (sus párrafos van dentro de la misma página).
 *  - Los ejemplos (entornos ejem / eje) se convierten en retos: enunciado en una
 *    página y solución en la siguiente. Si el ejemplo no tiene marcador de solución
 *    (\solution{}, entorno proof o un "Solución:" explícito) se muestra entero en
 *    una sola página como "Ejemplo resuelto".
 *  - Los ejercicios del preámbulo (\exercise{} / entorno ejer) y los problemas de
 *    la hoja son retos: enunciado y solución siempre en páginas separadas.
 */
class SheetPresentationHelper
{
    /** Marcadores que cortan el flujo del preámbulo. */
    private const MARKERS = [
        ['needle' => '\subsection*{', 'kind' => 'heading'],
        ['needle' => '\subsection{',  'kind' => 'heading'],
        ['needle' => '\section*{',    'kind' => 'heading'],
        ['needle' => '\section{',     'kind' => 'heading'],
        ['needle' => '\begin{ejem}',  'kind' => 'ejemplo', 'end' => '\end{ejem}'],
        ['needle' => '\begin{eje}',   'kind' => 'ejemplo', 'end' => '\end{eje}'],
        ['needle' => '\begin{ejer}',  'kind' => 'reto',    'end' => '\end{ejer}'],
        ['needle' => '\exercise{',    'kind' => 'reto'],
    ];

    /**
     * Construye el array de páginas.
     *
     * Cada página: [
     *   'kind'  => 'explicacion' | 'ejemplo' | 'reto' | 'solucion',
     *   'badge' => texto de la etiqueta superior,
     *   'title' => título a la derecha de la cabecera,
     *   'label' => etiqueta del botón de navegación,
     *   'group' => índice de unidad (reto y su solución comparten grupo),
     *   'html'  => contenido ya convertido,
     *   'hints' => HTML de pistas o null,
     * ]
     *
     * @param  iterable|null  $problemas  colección de App\Models\Problema
     */
    public static function build(?string $preambleTex, $problemas = null): array
    {
        LatexHelper::resetCounters();

        $pages = [];
        $counters = ['explicacion' => 0, 'ejemplo' => 0, 'reto' => 0, 'group' => 0];

        foreach (self::segmentPreamble((string) $preambleTex) as $segment) {
            self::appendPreamblePages($segment, $pages, $counters);
        }

        if ($problemas) {
            foreach ($problemas as $problema) {
                self::appendProblemPages($problema, $pages, $counters);
            }
        }

        return $pages;
    }

    // ------------------------------------------------------------------
    // Segmentación del preámbulo
    // ------------------------------------------------------------------

    /**
     * Recorre el TEX del preámbulo y lo parte en segmentos ordenados:
     *   ['type' => 'texto'|'ejemplo'|'reto', 'title' => ?string, 'tex' => string, 'solution' => ?string]
     */
    private static function segmentPreamble(string $tex): array
    {
        $segments = [];
        $buffer = '';
        $title = null;
        $pos = 0;
        $len = strlen($tex);

        while ($pos < $len) {
            $marker = self::nextMarker($tex, $pos);

            if ($marker === null) {
                $buffer .= substr($tex, $pos);
                break;
            }

            $buffer .= substr($tex, $pos, $marker['pos'] - $pos);
            $after = $marker['pos'] + strlen($marker['needle']);

            if ($marker['kind'] === 'heading') {
                $braced = self::readBraced($tex, $after);
                if ($braced === null) {
                    $buffer .= $marker['needle'];
                    $pos = $after;
                    continue;
                }
                self::flushText($segments, $buffer, $title);
                $title = LatexHelper::cleanLatexForDisplay($braced['inside']);
                $pos = $braced['end'];
                continue;
            }

            // Ejemplos y ejercicios: extraer el cuerpo del bloque
            if (isset($marker['end'])) {
                $close = strpos($tex, $marker['end'], $after);
                if ($close === false) {
                    $buffer .= $marker['needle'];
                    $pos = $after;
                    continue;
                }
                $inside = substr($tex, $after, $close - $after);
                $pos = $close + strlen($marker['end']);
            } else {
                $braced = self::readBraced($tex, $after);
                if ($braced === null) {
                    $buffer .= $marker['needle'];
                    $pos = $after;
                    continue;
                }
                $inside = $braced['inside'];
                $pos = $braced['end'];
            }

            self::flushText($segments, $buffer, $title);

            if ($marker['kind'] === 'reto') {
                // Un \solution{} inmediatamente después pertenece a este reto
                $solution = null;
                $trailing = self::readFollowingSolution($tex, $pos);
                if ($trailing !== null) {
                    $solution = $trailing['inside'];
                    $pos = $trailing['end'];
                }
                $segments[] = ['type' => 'reto', 'title' => $title, 'tex' => $inside, 'solution' => $solution];
            } else {
                $segments[] = ['type' => 'ejemplo', 'title' => $title, 'tex' => $inside, 'solution' => null];
            }
        }

        self::flushText($segments, $buffer, $title);

        return $segments;
    }

    private static function flushText(array &$segments, string &$buffer, ?string $title): void
    {
        if (trim($buffer) !== '') {
            $segments[] = ['type' => 'texto', 'title' => $title, 'tex' => $buffer, 'solution' => null];
        }
        $buffer = '';
    }

    /**
     * Devuelve el marcador que aparece antes en $tex a partir de $from, o null.
     */
    private static function nextMarker(string $tex, int $from): ?array
    {
        $best = null;

        foreach (self::MARKERS as $marker) {
            $pos = strpos($tex, $marker['needle'], $from);
            if ($pos === false) {
                continue;
            }
            if ($best === null || $pos < $best['pos']) {
                $best = $marker + ['pos' => $pos];
            }
        }

        return $best;
    }

    /**
     * Lee el contenido de un grupo de llaves ya abierto en $start (contando anidamiento).
     * Devuelve ['inside' => string, 'end' => int] con $end apuntando tras la llave de cierre.
     */
    private static function readBraced(string $tex, int $start): ?array
    {
        $depth = 1;
        $pos = $start;
        $len = strlen($tex);

        while ($pos < $len) {
            $char = $tex[$pos];

            if ($char === '\\') {
                $pos += 2; // saltar carácter escapado
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return ['inside' => substr($tex, $start, $pos - $start), 'end' => $pos + 1];
                }
            }

            $pos++;
        }

        return null;
    }

    /**
     * Si tras un reto solo hay espacios/comentarios y luego \solution{...}, lo devuelve.
     */
    private static function readFollowingSolution(string $tex, int $pos): ?array
    {
        $len = strlen($tex);
        $cursor = $pos;

        while ($cursor < $len) {
            $char = $tex[$cursor];
            if ($char === '%') {
                $eol = strpos($tex, "\n", $cursor);
                $cursor = $eol === false ? $len : $eol + 1;
                continue;
            }
            if (ctype_space($char)) {
                $cursor++;
                continue;
            }
            break;
        }

        if (substr($tex, $cursor, strlen('\solution{')) !== '\solution{') {
            return null;
        }

        return self::readBraced($tex, $cursor + strlen('\solution{'));
    }

    // ------------------------------------------------------------------
    // Ejemplos → retos
    // ------------------------------------------------------------------

    /**
     * Intenta partir el TEX de un ejemplo en enunciado + solución.
     * Devuelve ['statement' => string, 'solution' => string] o null si no hay marcador.
     */
    public static function splitStatementSolution(string $tex): ?array
    {
        // 1) \solution{...}
        $pos = strpos($tex, '\solution{');
        if ($pos !== false) {
            $braced = self::readBraced($tex, $pos + strlen('\solution{'));
            if ($braced !== null) {
                $statement = substr($tex, 0, $pos) . substr($tex, $braced['end']);
                return self::pair($statement, $braced['inside']);
            }
        }

        // 2) entorno proof (con o sin [Solución])
        if (preg_match('/\\\\begin\{proof\}(\[[^\]]*\])?/', $tex, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $bodyStart = $start + strlen($m[0][0]);
            $close = strpos($tex, '\end{proof}', $bodyStart);
            $solution = $close === false
                ? substr($tex, $bodyStart)
                : substr($tex, $bodyStart, $close - $bodyStart);
            return self::pair(substr($tex, 0, $start), $solution);
        }

        // 3) "Solución:" explícito (posiblemente dentro de \textbf{}, \emph{}...)
        $pattern = '/\\\\(?:textbf|textit|textsl|emph|underline)\s*\{\s*Soluci[óo]n\s*[:.]?\s*\}[:.]?'
                 . '|(?:^|\n)\s*Soluci[óo]n\s*[:.]/u';
        if (preg_match($pattern, $tex, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $end = $start + strlen($m[0][0]);
            return self::pair(substr($tex, 0, $start), substr($tex, $end));
        }

        return null;
    }

    /**
     * Valida que ambas mitades tengan contenido; si no, no hay partición útil.
     */
    private static function pair(string $statement, string $solution): ?array
    {
        if (trim($statement) === '' || trim($solution) === '') {
            return null;
        }

        return ['statement' => $statement, 'solution' => $solution];
    }

    // ------------------------------------------------------------------
    // Construcción de páginas
    // ------------------------------------------------------------------

    private static function appendPreamblePages(array $segment, array &$pages, array &$counters): void
    {
        if ($segment['type'] === 'texto') {
            $html = LatexHelper::toHtmlPreamble($segment['tex']);
            if (trim(strip_tags($html)) === '' && strpos($html, '<img') === false) {
                return;
            }
            $counters['explicacion']++;
            $counters['group']++;
            $pages[] = [
                'kind'  => 'explicacion',
                'badge' => 'Explicación',
                'title' => $segment['title'] ?: 'Explicación ' . $counters['explicacion'],
                'label' => 'T' . $counters['explicacion'],
                'group' => $counters['group'],
                'html'  => $html,
                'hints' => null,
            ];
            return;
        }

        if ($segment['type'] === 'reto') {
            $solution = $segment['solution'];
            if ($solution === null) {
                $split = self::splitStatementSolution($segment['tex']);
                $statement = $split ? $split['statement'] : $segment['tex'];
                $solution = $split ? $split['solution'] : null;
            } else {
                $statement = $segment['tex'];
            }

            self::pushReto(
                $pages,
                $counters,
                LatexHelper::toHtmlPreamble($statement),
                $solution === null ? null : LatexHelper::toHtmlPreamble($solution),
                $segment['title']
            );
            return;
        }

        // Ejemplo: se convierte en reto si se puede separar la solución
        $split = self::splitStatementSolution($segment['tex']);

        if ($split === null) {
            $counters['ejemplo']++;
            $counters['group']++;
            $pages[] = [
                'kind'  => 'ejemplo',
                'badge' => 'Ejemplo resuelto',
                'title' => 'Ejemplo ' . $counters['ejemplo'],
                'label' => 'Ej' . $counters['ejemplo'],
                'group' => $counters['group'],
                'html'  => LatexHelper::toHtmlPreamble($segment['tex']),
                'hints' => null,
            ];
            return;
        }

        self::pushReto(
            $pages,
            $counters,
            LatexHelper::toHtmlPreamble($split['statement']),
            LatexHelper::toHtmlPreamble($split['solution']),
            $segment['title']
        );
    }

    private static function appendProblemPages($problema, array &$pages, array &$counters): void
    {
        $statement = $problema->problem_html_processed;
        $solution = $problema->solution_tex
            ? $problema->solution_html_processed
            : null;
        $hints = $problema->hints ? LatexHelper::toHtml($problema->hints) : null;

        self::pushReto(
            $pages,
            $counters,
            $statement,
            $solution,
            $problema->title ?: 'Problema #' . $problema->id,
            $hints
        );
    }

    /**
     * Añade la página de reto y, si hay, la de su solución (comparten grupo de navegación).
     */
    private static function pushReto(
        array &$pages,
        array &$counters,
        string $statementHtml,
        ?string $solutionHtml,
        ?string $title = null,
        ?string $hints = null
    ): void {
        $counters['reto']++;
        $counters['group']++;
        $number = $counters['reto'];
        $group = $counters['group'];

        $pages[] = [
            'kind'  => 'reto',
            'badge' => 'Reto ' . $number,
            'title' => $title ?: '',
            'label' => 'R' . $number,
            'group' => $group,
            'html'  => $statementHtml,
            'hints' => $hints,
        ];

        $pages[] = [
            'kind'  => 'solucion',
            'badge' => 'Solución del reto ' . $number,
            'title' => $title ?: '',
            'label' => 'R' . $number,
            'group' => $group,
            'html'  => $solutionHtml ?: '<p style="color:#718096; font-style:italic;">No hay solución disponible para este reto.</p>',
            'hints' => null,
        ];
    }
}
