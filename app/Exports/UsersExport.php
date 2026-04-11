<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class UsersExport
 * Export users list to Excel.
 * (Xuất danh sách người dùng ra Excel)
 */
class UsersExport implements FromCollection, WithHeadings, WithMapping
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
            'Username',
            'Email',
            'Full Name',
            'Phone',
            'City',
            'Role',
            'Status',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $user
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->username,
            $user->email,
            $user->full_name,
            $user->phone ?? 'N/A',
            $user->city ?? 'N/A',
            $user->role,
            $user->status,
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
