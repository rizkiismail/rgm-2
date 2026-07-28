<?php

namespace App\Http\Controllers;

use App\Models\BalanceRetur;
use App\Services\BalanceReturImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BalanceReturImportController extends Controller
{
    public function form()
    {
        $lastImports = BalanceRetur::select('import_batch')
            ->whereNotNull('import_batch')
            ->distinct()
            ->orderByDesc('import_batch')
            ->limit(10)
            ->pluck('import_batch');

        return view('retur.import', [
            'lastImports' => $lastImports,
            'totalData' => BalanceRetur::count(),
        ]);
    }

    public function store(Request $request, BalanceReturImporter $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // maks 50MB
            'mode' => ['required', 'in:append,replace'],
        ], [
            'file.required' => 'Silakan pilih file export (.xlsx/.xls) terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 50MB.',
        ]);

        $uploaded = $request->file('file');
        $tmpPath = $uploaded->getRealPath();
        $batchLabel = now()->format('Y-m-d_H-i-s').'_'.Str::slug(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME));

        try {
            $result = $importer->importFromFile(
                $tmpPath,
                $batchLabel,
                truncateFirst: $request->mode === 'replace'
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Gagal memproses file: '.$e->getMessage()]);
        }

        $message = "Import selesai. {$result['total']} baris terbaca, {$result['inserted']} baris tersimpan/diperbarui";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} baris dilewati karena format tidak sesuai";
        }
        $message .= '.';

        return redirect()->route('retur.dashboard')->with('success', $message);
    }
}
