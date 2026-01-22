<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SourceHelper
{
    /**
     * Patrones para agrupar fuentes similares.
     * La clave es el nombre del grupo, el valor es un array de patrones regex.
     */
    private static $groupPatterns = [
        // Competiciones AMC/AIME/USAMO
        'AIME' => ['AIME'],
        'AMC 8' => ['AMC\s*8'],
        'AMC 10' => ['AMC\s*10'],
        'AMC 12' => ['AMC\s*12'],
        'USAMO' => ['USAMO'],
        'USAJMO' => ['USAJMO'],
        'USA TST' => ['USA\s*TST'],

        // Olimpiadas internacionales
        'IMO' => ['^IMO\b', 'International Mathematical Olympiad'],
        'IMO Shortlist' => ['IMO\s*Shortlist', 'IMO\s*SL'],
        'EGMO' => ['EGMO'],
        'APMO' => ['APMO'],
        'Balkan MO' => ['Balkan', 'BMO'],

        // Olimpiadas nacionales
        'OME' => ['^OME\b', 'Olimpiada.*Espa'],
        'OIM' => ['^OIM\b', 'Olimpiada.*Iberoamericana'],

        // Competiciones rusas y de Moscú
        'Olimpiada de Moscú' => ['Olimpiada.*Mosc', 'Moscow.*Olympiad', 'Mosc.*Olympiad'],
        'Olimpiada de Rusia' => ['Olimpiada.*Rusia', 'Russian.*Olympiad', 'Russia.*Olympiad', 'Olimpiada.*Rusa'],
        'Tournament of Towns' => ['Tournament.*Towns', 'Tornео.*ciudad', 'Torneo.*ciudades'],
        'Fiesta Matemática de Moscú' => ['Fiesta.*Matem.*Mosc', 'Moscow.*Math.*Festival'],

        // Competiciones por países
        'China' => ['China', 'Chinese'],
        'Russia' => ['Russia', 'Russian', 'USSR', 'Soviet'],
        'Romania' => ['Romania', 'Romanian', 'RMO'],
        'Hungary' => ['Hungar', 'Kurschak'],
        'Poland' => ['Poland', 'Polish'],
        'Bulgaria' => ['Bulgar'],

        // Autores conocidos (normalizar variantes)
        'A. Shen' => ['\bShen\b', 'A\.\s*Shen', 'Shen\s*A'],
        'Engel' => ['\bEngel\b'],
        'Andreescu' => ['\bAndreescu\b'],
        'Zeitz' => ['\bZeitz\b'],

        // Sitios web y círculos
        'We Solve Problems' => ['We\s*Solve\s*Problems', 'wesolveproblems'],
        'Problems.ru' => ['problems\.ru', 'problems\.com\.ru'],
        'Berkeley Math Circle' => ['Berkeley.*Math.*Circle', 'BMC'],

        // Otras competiciones
        'Putnam' => ['Putnam'],
        'MATHCOUNTS' => ['MATHCOUNTS', 'Mathcounts'],
        'Canguro' => ['Canguro', 'Kangourou', 'Kangaroo'],
    ];

    /**
     * Obtiene las fuentes agrupadas para mostrar en el desplegable.
     * Retorna un array con:
     * - 'groups' => grupos detectados con sus patrones
     * - 'ungrouped' => fuentes que no encajan en ningún grupo
     */
    public static function getGroupedSources(): array
    {
        // Obtener todas las fuentes únicas
        $rawSources = DB::table('pim_problems')
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->toArray();

        // Expandir fuentes con comas en partes individuales
        $allSources = self::expandSourcesWithCommas($rawSources);

        $groups = [];
        $usedSources = [];

        // Agrupar fuentes por patrones
        foreach (self::$groupPatterns as $groupName => $patterns) {
            $matchingSources = [];

            foreach ($allSources as $source) {
                foreach ($patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $source)) {
                        $matchingSources[] = $source;
                        $usedSources[$source] = true;
                        break;
                    }
                }
            }

            if (count($matchingSources) > 0) {
                $groups[$groupName] = [
                    'count' => count($matchingSources),
                    'sources' => $matchingSources,
                ];
            }
        }

        // Fuentes no agrupadas
        $ungrouped = [];
        foreach ($allSources as $source) {
            if (!isset($usedSources[$source])) {
                $ungrouped[] = $source;
            }
        }

        // Ordenar grupos por nombre
        ksort($groups);

        return [
            'groups' => $groups,
            'ungrouped' => $ungrouped,
        ];
    }

    /**
     * Obtiene los patrones de búsqueda SQL para un grupo dado.
     * Si es un grupo conocido, devuelve array de patrones LIKE.
     * Si no, devuelve el valor exacto.
     */
    public static function getSearchPatterns(string $sourceFilter): array
    {
        // Si empieza con "group:", es un grupo
        if (strpos($sourceFilter, 'group:') === 0) {
            $groupName = substr($sourceFilter, 6);

            if (isset(self::$groupPatterns[$groupName])) {
                // Convertir patrones regex a patrones LIKE
                $likePatterns = [];
                foreach (self::$groupPatterns[$groupName] as $pattern) {
                    // Simplificar el regex a un patrón LIKE básico
                    $like = preg_replace('/[\^\$\\\\]/', '', $pattern);
                    $like = preg_replace('/\\\\s\*/', '%', $like);
                    $like = preg_replace('/\.\*/', '%', $like);
                    $like = preg_replace('/\\\\b/', '', $like);
                    $likePatterns[] = '%' . $like . '%';
                }
                return $likePatterns;
            }
        }

        // Valor exacto
        return [$sourceFilter];
    }

    /**
     * Aplica el filtro de fuente a una query.
     */
    public static function applySourceFilter($query, string $sourceFilter)
    {
        if (empty($sourceFilter)) {
            return $query;
        }

        $patterns = self::getSearchPatterns($sourceFilter);

        if (count($patterns) === 1 && strpos($patterns[0], '%') === false) {
            // Búsqueda exacta
            return $query->where('source', $patterns[0]);
        }

        // Búsqueda con LIKE para múltiples patrones
        return $query->where(function($q) use ($patterns) {
            foreach ($patterns as $pattern) {
                $q->orWhere('source', 'LIKE', $pattern);
            }
        });
    }

    /**
     * Cuenta problemas por grupo de fuente.
     */
    public static function countByGroup(string $groupName): int
    {
        if (!isset(self::$groupPatterns[$groupName])) {
            return 0;
        }

        $query = DB::table('pim_problems')->whereNotNull('source');

        $patterns = self::$groupPatterns[$groupName];
        $query->where(function($q) use ($patterns) {
            foreach ($patterns as $pattern) {
                // Convertir regex a LIKE
                $like = preg_replace('/[\^\$\\\\]/', '', $pattern);
                $like = preg_replace('/\\\\s\*/', '%', $like);
                $like = preg_replace('/\.\*/', '%', $like);
                $like = preg_replace('/\\\\b/', '', $like);
                $q->orWhere('source', 'LIKE', '%' . $like . '%');
            }
        });

        return $query->count();
    }

    /**
     * Expande fuentes que contienen comas en partes individuales.
     * Por ejemplo: "A. Shen, Engel" -> ["A. Shen", "Engel"]
     * Mantiene también la fuente original para búsquedas exactas.
     */
    private static function expandSourcesWithCommas(array $rawSources): array
    {
        $expanded = [];
        $seen = [];

        foreach ($rawSources as $source) {
            // Si contiene coma, separar en partes
            if (strpos($source, ',') !== false) {
                $parts = array_map('trim', explode(',', $source));
                foreach ($parts as $part) {
                    if (!empty($part) && !isset($seen[$part])) {
                        $expanded[] = $part;
                        $seen[$part] = true;
                    }
                }
            } else {
                if (!isset($seen[$source])) {
                    $expanded[] = $source;
                    $seen[$source] = true;
                }
            }
        }

        // Ordenar alfabéticamente
        sort($expanded, SORT_STRING | SORT_FLAG_CASE);

        return $expanded;
    }

    /**
     * Aplica el filtro buscando también en fuentes compuestas (con comas).
     * Si el usuario selecciona "A. Shen", también encuentra "A. Shen, Engel".
     */
    public static function applySourceFilterWithCommas($query, string $sourceFilter)
    {
        if (empty($sourceFilter)) {
            return $query;
        }

        // Si es un grupo, usar la lógica de grupos
        if (strpos($sourceFilter, 'group:') === 0) {
            return self::applySourceFilter($query, $sourceFilter);
        }

        // Buscar coincidencia exacta O como parte de una lista con comas
        return $query->where(function($q) use ($sourceFilter) {
            $q->where('source', $sourceFilter)
              ->orWhere('source', 'LIKE', $sourceFilter . ',%')
              ->orWhere('source', 'LIKE', '%, ' . $sourceFilter)
              ->orWhere('source', 'LIKE', '%, ' . $sourceFilter . ',%');
        });
    }
}
