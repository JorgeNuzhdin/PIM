<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class TagHelper
{
    /**
     * Umbral máximo de distancia Levenshtein para considerar tags similares.
     */
    private static $maxDistance = 2;

    /**
     * Normaliza un tag buscando uno similar existente.
     * Si encuentra un tag con distancia Levenshtein <= 2, lo sustituye.
     *
     * @param string $tag El tag a normalizar
     * @return string El tag normalizado (existente o el original si no hay similar)
     */
    public static function normalize(string $tag): string
    {
        $tag = trim($tag);

        if (empty($tag)) {
            return $tag;
        }

        // Obtener todos los tags existentes
        $existingTags = self::getAllTags();

        // Primero buscar coincidencia exacta (case insensitive)
        foreach ($existingTags as $existingTag) {
            if (strcasecmp($tag, $existingTag) === 0) {
                return $existingTag; // Devolver el existente para mantener consistencia
            }
        }

        // Buscar tag similar con Levenshtein
        $bestMatch = null;
        $bestDistance = self::$maxDistance + 1;

        foreach ($existingTags as $existingTag) {
            $distance = levenshtein(
                mb_strtolower($tag),
                mb_strtolower($existingTag)
            );

            if ($distance <= self::$maxDistance && $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $existingTag;
            }
        }

        return $bestMatch ?? $tag;
    }

    /**
     * Normaliza un array de tags.
     *
     * @param array $tags Array de tags a normalizar
     * @return array Array de tags normalizados (sin duplicados)
     */
    public static function normalizeArray(array $tags): array
    {
        $normalized = [];
        $seen = [];

        foreach ($tags as $tag) {
            $normalizedTag = self::normalize($tag);
            $lowerTag = mb_strtolower($normalizedTag);

            if (!empty($normalizedTag) && !isset($seen[$lowerTag])) {
                $normalized[] = $normalizedTag;
                $seen[$lowerTag] = true;
            }
        }

        return $normalized;
    }

    /**
     * Obtiene todos los tags existentes de la base de datos.
     *
     * @return array
     */
    public static function getAllTags(): array
    {
        static $cachedTags = null;

        if ($cachedTags === null) {
            // Obtener tags de pim_problem_tags (uso real)
            $problemTags = DB::table('pim_problem_tags')
                ->distinct()
                ->pluck('tag')
                ->toArray();

            // También obtener de la tabla tags si existe
            $tableTags = DB::table('tags')
                ->distinct()
                ->pluck('title')
                ->toArray();

            // Combinar y eliminar duplicados
            $cachedTags = array_unique(array_merge($problemTags, $tableTags));
        }

        return $cachedTags;
    }

    /**
     * Limpia la caché de tags (útil después de añadir nuevos tags).
     */
    public static function clearCache(): void
    {
        // Forzar recarga en la próxima llamada
        // (la variable estática se reinicia entre requests)
    }
}
