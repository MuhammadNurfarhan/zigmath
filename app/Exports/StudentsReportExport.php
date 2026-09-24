<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'Kelas',
            'Paket',
            'Orang Tua',
            'No HP Orang Tua',
            'Sekolah',
            'Jatuh Tempo',
            'Status',
            'Total Tagihan',
            'Total Terbayar',
            'Sisa Tagihan',
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->class_type === 'regular' ? 'Reguler' : 'Private',
            $row->package->name ?? '-',
            $row->parent_name,
            $row->parent_phone,
            $row->school ?? '-',
            'Tanggal '.$row->due_day,
            match ($row->status) {
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                'cuti' => 'Cuti',
                default => ucfirst($row->status),
            },
            $row->invoices_sum_amount ?? 0,
            $row->invoices_sum_paid_amount ?? 0,
            $row->invoices_sum_remaining_balance ?? 0,
        ];
    }
}
