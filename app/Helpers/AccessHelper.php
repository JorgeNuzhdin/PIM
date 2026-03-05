<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Models\PimSheet;
use Illuminate\Support\Facades\Auth;

class AccessHelper
{
    /**
     * ¿El usuario actual tiene acceso restringido (roles 'user' y 'user_seguro')?
     */
    public static function isRestricted(): bool
    {
        if (!Auth::check()) return false;
        return in_array(Auth::user()->rol, ['user', 'user_seguro']);
    }

    /**
     * Años académicos (date_year) accesibles para el usuario actual.
     * Devuelve null si no hay restricción.
     */
    public static function allowedYears(): ?array
    {
        if (!static::isRestricted()) return null;

        $accessYears = (int) Setting::get('access_years', 2);
        $maxYear = PimSheet::max('date_year');

        if (!$maxYear) return [];

        $years = [];
        for ($i = 0; $i < $accessYears; $i++) {
            $years[] = $maxYear - $i;
        }
        return $years;
    }

    /**
     * IDs de problemas accesibles para el usuario actual.
     * Devuelve null si no hay restricción.
     */
    public static function allowedProblemIds(): ?array
    {
        $years = static::allowedYears();
        if ($years === null) return null;
        if (empty($years)) return [];

        $sheets = PimSheet::whereIn('date_year', $years)->pluck('problems');

        $ids = [];
        foreach ($sheets as $problems) {
            if (empty($problems)) continue;
            foreach (array_filter(array_map('trim', explode(',', $problems))) as $id) {
                $ids[] = (int) $id;
            }
        }
        return array_unique($ids);
    }
}
