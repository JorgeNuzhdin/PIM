<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'access_years'          => 'required|integer|min:1|max:10',
            'min_problemas_medalla' => 'required|integer|min:1|max:100',
        ]);

        Setting::set('access_years', $request->access_years);
        Setting::set('min_problemas_medalla', $request->min_problemas_medalla);

        return back()->with('success', 'Configuración guardada.');
    }

    public function exportDb()
    {
        $tables = DB::select('SHOW TABLES');
        $dbKey  = 'Tables_in_' . DB::getDatabaseName();

        $zipPath = storage_path('app/temp/db_export_' . date('Ymd_His') . '.zip');
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($tables as $tableRow) {
            $table = $tableRow->$dbKey;
            $rows  = DB::table($table)->get();

            if ($rows->isEmpty()) {
                $zip->addFromString("{$table}.csv", '');
                continue;
            }

            $cols = array_keys((array) $rows->first());
            $csv  = implode(',', array_map(fn($c) => '"' . addcslashes($c, '"') . '"', $cols)) . "\n";

            foreach ($rows as $row) {
                $row = (array) $row;
                $csv .= implode(',', array_map(function ($val) {
                    if ($val === null) return '';
                    // Omitir campos binarios grandes (imágenes, PDFs)
                    if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) return '"[BINARY]"';
                    $val = str_replace(["\r\n", "\r", "\n"], ' ', $val);
                    return '"' . addcslashes((string) $val, '"') . '"';
                }, $row)) . "\n";
            }

            $zip->addFromString("{$table}.csv", $csv);
        }

        $zip->close();

        return response()->download($zipPath, 'db_export_' . date('Ymd_His') . '.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
