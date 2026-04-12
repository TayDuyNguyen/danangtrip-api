<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class ContactsExport
 * Export contacts list to Excel.
 * (Xuất danh sách liên hệ ra Excel)
 */
class ContactsExport implements FromCollection, WithHeadings, WithMapping
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
            'Email',
            'Phone',
            'Subject',
            'Message',
            'Status',
            'Replied By',
            'Replied At',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $contact
     */
    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->name,
            $contact->email,
            $contact->phone ?? 'N/A',
            $contact->subject ?? 'N/A',
            $contact->message,
            $contact->status,
            $contact->replier->username ?? 'N/A',
            $contact->replied_at ? $contact->replied_at->format('Y-m-d H:i:s') : 'N/A',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
