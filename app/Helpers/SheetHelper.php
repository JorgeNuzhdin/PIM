<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SheetHelper
{
    /**
     * Obtiene los problemas usados en hojas que comparten institución con el usuario.
     * Retorna un array [problema_id => año_más_reciente]
     */
    public static function getProblemasUsadosConAnio(): array
    {
        $user = Auth::user();

        if (!$user || empty($user->institution)) {
            return [];
        }

        // Obtener instituciones del usuario (separadas por comas, trim espacios)
        $userInstitutions = array_map('trim', explode(',', $user->institution));

        // Obtener todas las hojas de pim_sheets
        $sheets = DB::table('pim_sheets')
            ->whereNotNull('institution')
            ->where('institution', '!=', '')
            ->whereNotNull('problems')
            ->where('problems', '!=', '')
            ->get(['id', 'institution', 'problems', 'date_year']);

        $problemasUsados = [];

        foreach ($sheets as $sheet) {
            // Verificar intersección de instituciones
            $sheetInstitutions = array_map('trim', explode(',', $sheet->institution));

            $hasIntersection = !empty(array_intersect($userInstitutions, $sheetInstitutions));

            if ($hasIntersection) {
                // Extraer IDs de problemas (separados por comas)
                $problemIds = array_map('trim', explode(',', $sheet->problems));

                foreach ($problemIds as $problemId) {
                    if (is_numeric($problemId)) {
                        $problemId = (int)$problemId;

                        // Guardar el año más reciente para cada problema
                        if (!isset($problemasUsados[$problemId]) || $sheet->date_year > $problemasUsados[$problemId]) {
                            $problemasUsados[$problemId] = $sheet->date_year;
                        }
                    }
                }
            }
        }

        return $problemasUsados;
    }
}
