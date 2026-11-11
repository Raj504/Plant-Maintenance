<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns report data (columns + rows) into a CSV download (opens in Excel)
 * or a PDF (dompdf). Print uses the browser's print of the HTML report page.
 */
class ReportExportService
{
    public static function csv(string $title, array $columns, array $rows, ?array $totals = null): StreamedResponse
    {
        $filename = \Illuminate\Support\Str::slug($title) . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($columns, $rows, $totals) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₹ and accents correctly
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($key) => $row[$key] ?? '', array_keys($columns)));
            }
            foreach ($totals ?? [] as $row) {
                fputcsv($out, array_map(fn ($key) => $row[$key] ?? '', array_keys($columns)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function pdf(string $title, array $columns, array $rows, array $filters, ?array $totals = null)
    {
        $pdf = Pdf::loadView('reports.pdf', compact('title', 'columns', 'rows', 'filters', 'totals'))
            ->setPaper('a4', count($columns) > 7 ? 'landscape' : 'portrait');

        return $pdf->download(\Illuminate\Support\Str::slug($title) . '-' . now()->format('Ymd-His') . '.pdf');
    }
}
