<?php

namespace App\Http\Controllers\Puskesmas;

use App\Http\Controllers\Controller;
use App\Services\TerritoryReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly TerritoryReportService $reports) {}

    public function index(Request $request): \Illuminate\View\View
    {
        return view('puskesmas.reports', [
            'summary' => $this->reports->summary($request->user()),
            'posyandu' => $this->reports->posyanduComparison($request->user()),
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $rows = $this->reports->posyanduComparison($request->user());
        $filename = 'laporan-posyandu-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Posyandu', 'Kode', 'Sasaran Balita', 'Ditimbang Bulan Ini', 'Cakupan (%)', 'Pendek', 'Wasting', 'Perlu Ditinjau']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['code'],
                    $row['total_target'],
                    $row['measured'],
                    $row['coverage_percent'],
                    $row['stunting'],
                    $row['wasting'],
                    $row['open_alerts'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
