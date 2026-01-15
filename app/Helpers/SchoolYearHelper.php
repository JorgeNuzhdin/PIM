<?php

namespace App\Helpers;

class SchoolYearHelper
{
    private static $years = [
        1 => '1 Primaria',
        2 => '2 Primaria',
        3 => '3 Primaria',
        4 => '4 Primaria',
        5 => '5 Primaria',
        6 => '6 Primaria',
        7 => '1 ESO',
        8 => '2 ESO',
        9 => '3 ESO',
        10 => '4 ESO',
        11 => '1 BACH',
        12 => '2 BACH',
    ];
    
    public static function getYearName($index)
    {
        return self::$years[$index] ?? $index ;
    }
    
    public static function getAllYears()
    {
        return self::$years;
    }
}