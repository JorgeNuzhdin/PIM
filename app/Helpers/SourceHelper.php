<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Helpers\LatexHelper;

class SourceHelper
{
    /**
     * Número mínimo de problemas para que un grupo aparezca en "Grupos".
     * Los grupos más pequeños se muestran dentro de "Otras fuentes".
     */
    const MIN_GROUP_SIZE = 10;

    /**
     * Patrones para agrupar fuentes similares.
     * La clave es el nombre del grupo, el valor es un array de patrones regex
     * (sin delimitadores; se aplican con /iu sobre cada parte separada por comas).
     */
    private static $groupPatterns = [
        'Baltic Way' => ['Baltic\s*Way'],

        // Competiciones AMC/AIME/USAMO
        'AIME' => ['AIME'],
        'AMC 8' => ['AMC\s*8'],
        'AMC 10' => ['AMC\s*10'],
        'AMC 12' => ['AMC\s*12'],
        'USAMO' => ['USAMO'],
        'USAJMO' => ['USAJMO'],
        'USA TST' => ['USA\s*TST'],

        // Olimpiadas internacionales
        'IMO' => ['^IMO\b', 'International Mathematical Olympiad', 'IMO\s*Shortlist', 'IMO\s*SL'],
        'EGMO' => ['EGMO'],
        'APMO' => ['APMO'],
        'Olimpiada de los Balcanes' => ['Balkan', 'BMO', 'Olimpiada.*Balc', 'Balcánica'],

        // Olimpiadas nacionales
        'OME' => ['^OME\b', 'Olimpiada.*Espa', 'fase.*local.*OME'],
        'OIM' => ['^OIM\b', 'Olimpiada.*Iberoamericana'],
        'Olimpiada de Irán' => ['Olimpiada.*Ir[aá]n\b', '\bIranian\b'],
        'Concurso de Primavera' => ['Concurso.*Primavera', 'Primavera.*Matem'],

        // Competiciones rusas y de Moscú
        'Olimpiada de Moscú' => ['Olimpiada.*Mosc', 'Moscow.*Olympiad', 'Mosc.*Olympiad'],
        'Olimpiada de Rusia' => ['Olimpiada.*Rusia', 'Russian.*Olympiad', 'Russia.*Olympiad', 'Olimpiada.*Rusa', 'Russia', 'Russian', '\bRusia\b', 'Olimpiada\s*Matem[aá]tica\s*Oral', 'USSR', 'Soviet'],
        'Tournament of Towns' => ['Tournament.*Towns', 'Tornео.*ciudad', 'Torneo.*ciudades'],
        'Fiesta Matemática de Moscú' => ['Fiesta.*Matem.*Mosc', 'Fiesta\s*Matem[aá]tica\s*de\s*\d{4}', 'Moscow.*Math.*Festival'],

        // Competiciones por países
        'China' => ['China', 'Chinese'],
        'Romania' => ['Romania', 'Romanian', 'RMO'],
        'Hungary' => ['Hungar', 'Kurschak'],
        'Poland' => ['Poland', 'Polish'],
        'Bulgaria' => ['Bulgar'],

        // Autores conocidos (normalizar variantes)
        'Folklore' => ['\bfol[kc]lore\b', '\bcl[aá]sico\b'],
        'A. Shen' => ['\bShen\b', 'A\.\s*Shen', 'Shen\s*A'],
        'A. Shapovalov' => ['\bShapovalov\b', 'A\.\s*Shapovalov', 'Shapovalov\s*A'],
        'A. Skopenkov - A. Zaslavsky' => ['Skopenkov', 'Zaslavsk[iy](?!\s*O\b)', 'Math(ematics)?\s*via\s*Problems'],
        'A. Ryabichev' => ['R[iy]abichev'],
        'M. Evdokimov' => ['E[uv]dok[ií]m?[oó]v', 'Eudokomov'],
        'I. Yaschenko' => ['Yas(c)?hchenko', 'Yaschenko'],
        'I. Yakovlev' => ['I\.\s*Yakovlev'],
        'J. Ponarin' => ['Ponarin'],
        'A. Knop' => ['Knop\s*A\b'],
        'V. Bragin' => ['\bBragin\b'],
        'Catriona Agg' => ['Catriona\s*Agg'],
        'A. Gribalko' => ['Gribalko'],
        'A. Peshnin' => ['Peshnin'],
        'N. Konstantinov' => ['\bKonstantinov\b', 'N\.\s*Konstantinov', 'Konstantinov\s*N'],
        'M. Volchkevich' => ['\bVolchkevich\b', 'M\.\s*Volchkevich', 'Volchkevich\s*M', 'Maxim\s*Volchkevich'],
        'A. Kanel-Belov' => ['\bKanel-Belov\b', '\bKanel\b', 'A\.\s*Kanel', 'Kanel-Belov\s*A'],
        'M. Gardner' => ['\bGardner\b', 'M\.\s*Gardner', 'Martin\s*Gardner', 'Gardner\s*M'],
        'V. Prasolov' => ['\bPrasolov\b', 'V\.\s*Prasolov', 'Prasolov\s*V'],
        'Engel' => ['\bEngel\b'],
        'Andreescu' => ['\bAndreescu\b'],
        'Zeitz' => ['\bZeitz\b'],
        'R. Smullyan' => ['\bSmullyan\b', 'La dama o el tigre', 'The Lady or the Tiger'],
        'Martínez Sandoval' => ['Mart[ií]nez\s*Sandoval', 'ecuaciones\s*funcionales'],
        // Sitios web y círculos
        'We Solve Problems' => ['We\s*Solve\s*Problems', 'wesolveproblems'],
        'Problems.ru' => ['problems\.ru', 'problems\.com\.ru'],
        'zadachi.mccme.ru' => ['zadachi\.mccme', 'mccme\.ru'],
        'Art of Problem Solving' => ['Art\s*of\s*Problem', 'AoPS', 'artofproblemsolving'],
        'Berkeley Math Circle' => ['Berkeley.*Math.*Circle', 'BMC', 'Berkeley\s*MC'],
        'Círculos Matemáticos' => ['Círculo.*Matem', 'Circulo.*Matem', 'Math.*Circle'],
        'Skolkovo' =>['Skolkovo'],

        // Libros
        'Problem Solving Strategies' => ['Problem\s*Solving\s*Strategies', 'Engel.*Strategies'],
        'Excalibur' => ['Excalibur'],

        // Otras competiciones
        'Putnam' => ['Putnam'],
        'MATHCOUNTS' => ['MATHCOUNTS', 'Mathcounts'],
        'Canguro' => ['Canguro', 'Cangur\b', 'Kangourou', 'Kangaroo'],
    ];

    /** Conteo de fuentes crudas (source => nº de problemas), cacheado por petición. */
    private static $rawSourceCounts = null;

    /**
     * Obtiene las fuentes agrupadas para mostrar en el desplegable.
     * Retorna un array con:
     * - 'groups' => grupos con al menos MIN_GROUP_SIZE problemas (nombre => ['count', 'sources'])
     * - 'others' => lista ordenada de ['value', 'label', 'count']: grupos pequeños
     *   y fuentes sueltas que no encajan en ningún grupo (solo las que aparecen 2+ veces)
     */
    public static function getGroupedSources(): array
    {
        return self::groupSources(self::rawSourceCounts());
    }

    /**
     * Agrupa un array source => count (separado de la BD para poder probarlo).
     */
    public static function groupSources(array $sourceCounts): array
    {
        // Expandir fuentes con comas en partes individuales
        $allSources = self::expandSourcesWithCommas(array_keys($sourceCounts), $sourceCounts);

        $groups = [];
        $usedSources = [];

        // Agrupar fuentes por patrones. El conteo es de problemas (fuentes crudas),
        // para que "Skopenkov, Zaslavsky" no cuente dos veces en el mismo grupo.
        foreach (self::$groupPatterns as $groupName => $patterns) {
            $matchingSources = [];
            foreach (array_keys($allSources) as $source) {
                if (self::matchesAny($source, $patterns)) {
                    $matchingSources[] = $source;
                    $usedSources[$source] = true;
                }
            }

            if (count($matchingSources) > 0) {
                $totalCount = 0;
                foreach (self::rawSourcesForGroup($groupName, $sourceCounts) as $raw) {
                    $totalCount += $sourceCounts[$raw];
                }
                $groups[$groupName] = [
                    'count' => $totalCount,
                    'sources' => $matchingSources,
                ];
            }
        }

        $others = [];

        // Los grupos poco relevantes bajan a "Otras fuentes"
        foreach ($groups as $groupName => $info) {
            if ($info['count'] < self::MIN_GROUP_SIZE) {
                if ($info['count'] >= 2) {
                    $others[] = ['value' => 'group:' . $groupName, 'label' => $groupName, 'count' => $info['count']];
                }
                unset($groups[$groupName]);
            }
        }

        // Fuentes no agrupadas (solo las que aparecen al menos 2 veces y no son solo números)
        foreach ($allSources as $source => $count) {
            // Excluir fuentes que son solo números o rangos de años (ej: "2020", "2020-2021")
            if (preg_match('/^\d{4}(-\d{4})?$/', $source)) {
                continue;
            }
            if (!isset($usedSources[$source]) && $count >= 2) {
                $others[] = ['value' => $source, 'label' => LatexHelper::cleanLatexForDisplay($source), 'count' => $count];
            }
        }

        // Ordenar grupos y otras fuentes por nombre
        uksort($groups, 'strcasecmp');
        usort($others, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        return [
            'groups' => $groups,
            'others' => $others,
        ];
    }

    /**
     * Devuelve las fuentes crudas de la BD que pertenecen a un grupo
     * (alguna de sus partes separadas por comas encaja con un patrón del grupo).
     */
    public static function rawSourcesForGroup(string $groupName, ?array $sourceCounts = null): array
    {
        $patterns = self::$groupPatterns[$groupName] ?? null;
        if ($patterns === null) {
            return [];
        }

        $result = [];
        foreach (array_keys($sourceCounts ?? self::rawSourceCounts()) as $source) {
            foreach (self::splitSource($source) as $part) {
                if (self::matchesAny($part, $patterns)) {
                    $result[] = $source;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Aplica el filtro de fuente a una query.
     */
    public static function applySourceFilter($query, string $sourceFilter)
    {
        if (empty($sourceFilter)) {
            return $query;
        }

        if (strpos($sourceFilter, 'group:') === 0) {
            $sources = self::rawSourcesForGroup(substr($sourceFilter, 6));
            if (empty($sources)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('source', $sources);
        }

        // Búsqueda exacta
        return $query->where('source', $sourceFilter);
    }

    /**
     * Cuenta problemas por grupo de fuente.
     */
    public static function countByGroup(string $groupName): int
    {
        $counts = self::rawSourceCounts();
        $total = 0;
        foreach (self::rawSourcesForGroup($groupName, $counts) as $source) {
            $total += $counts[$source];
        }
        return $total;
    }

    private static function rawSourceCounts(): array
    {
        if (self::$rawSourceCounts === null) {
            self::$rawSourceCounts = DB::table('pim_problems')
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->select('source', DB::raw('COUNT(*) as count'))
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();
        }
        return self::$rawSourceCounts;
    }

    private static function matchesAny(string $source, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $source)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Separa por comas que NO estén precedidas por '\' (para no romper LaTeX como \,)
     */
    private static function splitSource(string $source): array
    {
        $parts = preg_split('/(?<!\\\\),/', $source);
        if ($parts === false) {
            return [$source];
        }
        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /**
     * Expande fuentes que contienen comas en partes individuales.
     * Por ejemplo: "A. Shen, Engel" -> ["A. Shen" => count, "Engel" => count]
     * Retorna un array asociativo con el conteo de cada fuente.
     */
    private static function expandSourcesWithCommas(array $rawSources, array $sourceCounts): array
    {
        $expanded = [];

        foreach ($rawSources as $source) {
            $count = $sourceCounts[$source] ?? 1;
            foreach (self::splitSource($source) as $part) {
                $expanded[$part] = ($expanded[$part] ?? 0) + $count;
            }
        }

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
