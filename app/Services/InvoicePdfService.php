<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Str;

class InvoicePdfService
{
    public function render(Booking $booking): string
    {
        $lines = $this->buildInvoiceLines($booking);
        $content = $this->buildPageContent($lines);

        return $this->buildPdf($content);
    }

    private function buildInvoiceLines(Booking $booking): array
    {
        $items = $booking->items ?? collect();
        $lines = [
            'DA NANG TRIP - BOOKING INVOICE',
            'Invoice for booking: '.$booking->booking_code,
            'Generated at: '.now()->format('Y-m-d H:i:s'),
            '',
            'CUSTOMER',
            'Name: '.$this->ascii($booking->customer_name ?: ($booking->user?->full_name ?? 'Guest')),
            'Email: '.$this->ascii($booking->customer_email ?: ($booking->user?->email ?? 'N/A')),
            'Phone: '.$this->ascii($booking->customer_phone ?: 'N/A'),
            '',
            'BOOKING',
            'Booked at: '.optional($booking->booked_at)->format('Y-m-d H:i:s'),
            'Booking status: '.$this->ascii((string) $booking->booking_status),
            'Payment status: '.$this->ascii((string) $booking->payment_status),
            'Payment method: '.$this->ascii((string) ($booking->payment_method ?: 'N/A')),
            '',
            'ITEMS',
        ];

        foreach ($items as $index => $item) {
            $name = $item->item_name ?: ($item->tour?->name ?? 'Tour item');
            $quantity = ((int) $item->quantity_adult) + ((int) $item->quantity_child) + ((int) $item->quantity_infant);
            $lines[] = ($index + 1).'. '.$this->ascii($name);
            $lines[] = '   Travel date: '.$this->ascii((string) $item->travel_date).' | Quantity: '.$quantity.' | Subtotal: '.$this->money($item->subtotal).' VND';
        }

        if ($items->isEmpty()) {
            $lines[] = 'No items found.';
        }

        $lines = array_merge($lines, [
            '',
            'PAYMENT SUMMARY',
            'Total amount: '.$this->money($booking->total_amount).' VND',
            'Discount: '.$this->money($booking->discount_amount).' VND',
            'Deposit: '.$this->money($booking->deposit_amount).' VND',
            'Final amount: '.$this->money($booking->final_amount ?: $booking->total_amount).' VND',
            '',
            'Thank you for using DaNangTrip.',
        ]);

        return $lines;
    }

    private function buildPageContent(array $lines): string
    {
        $escapedLines = array_map(fn (string $line) => '('.$this->escapePdfText(Str::limit($line, 105, '')).') Tj T*', $lines);

        return "BT\n/F1 10 Tf\n14 TL\n50 790 Td\n".implode("\n", $escapedLines)."\nET";
    }

    private function buildPdf(string $content): string
    {
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            '5 0 obj << /Length '.strlen($content).' >> stream'."\n".$content."\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->ascii($text));
    }

    private function ascii(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return preg_replace('/[^\x20-\x7E]/', '', $converted !== false ? $converted : $value) ?? '';
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
