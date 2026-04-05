<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ToursExport implements FromCollection, WithHeadings, WithMapping
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
            'Name',
            'Slug',
            'Category',
            'Price Adult',
            'Price Child',
            'Price Infant',
            'Discount %',
            'Duration',
            'Status',
            'Featured',
            'Hot',
            'View Count',
            'Booking Count',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $tour
     */
    public function map($tour): array
    {
        return [
            $tour->id,
            $tour->name,
            $tour->slug,
            $tour->category->name ?? 'N/A',
            $tour->price_adult,
            $tour->price_child,
            $tour->price_infant,
            $tour->discount_percent,
            $tour->duration,
            $tour->status,
            $tour->is_featured ? 'Yes' : 'No',
            $tour->is_hot ? 'Yes' : 'No',
            $tour->view_count,
            $tour->booking_count,
            $tour->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
