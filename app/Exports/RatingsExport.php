<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RatingsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $data
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Type',
            'Item Name',
            'Score',
            'Comment',
            'Status',
            'Helpful Count',
            'Approved By',
            'Approved At',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $rating
     */
    public function map($rating): array
    {
        $type = 'Unknown';
        $itemName = 'N/A';

        if ($rating->location_id) {
            $type = 'Location';
            $itemName = $rating->location->name ?? 'N/A';
        } elseif ($rating->tour_id) {
            $type = 'Tour';
            $itemName = $rating->tour->name ?? 'N/A';
        }

        return [
            $rating->id,
            $rating->user->username ?? 'N/A',
            $type,
            $itemName,
            $rating->score,
            $rating->comment,
            $rating->status,
            $rating->helpful_count,
            $rating->approver->username ?? 'N/A',
            $rating->approved_at ? $rating->approved_at->format('Y-m-d H:i:s') : 'N/A',
            $rating->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
