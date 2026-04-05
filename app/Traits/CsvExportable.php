<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trait CsvExportable
 * Provides functionality to export data to CSV.
 * (Cung cấp chức năng xuất dữ liệu ra CSV)
 */
trait CsvExportable
{
    /**
     * Export data to CSV with multiple options.
     * (Xuất dữ liệu ra CSV với nhiều tùy chọn)
     */
    protected function exportToCsv(
        array $data,
        string $filenamePrefix = 'export',
        array $customHeaders = []
    ): StreamedResponse {
        $headers = array_merge([
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filenamePrefix.'_'.date('YmdHis').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ], $customHeaders);

        $callback = function () use ($data, $customHeaders) {
            $file = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            if (! empty($data)) {
                // Get headers from keys of the first element or from customHeaders
                $headersRow = ! empty($customHeaders['headers'])
                    ? $customHeaders['headers']
                    : array_keys($data[0]);

                fputcsv($file, $headersRow);

                foreach ($data as $row) {
                    // Sanitize against CSV Injection
                    $sanitizedRow = array_map(function ($value) {
                        $strVal = (string) $value;
                        if (preg_match('/^[=+\-@]/', $strVal)) {
                            return "'".$strVal;
                        }

                        return $strVal;
                    }, (array) $row);

                    fputcsv($file, $sanitizedRow);
                }
            } else {
                // Case when no data is available
                fputcsv($file, ['No data available to export']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
