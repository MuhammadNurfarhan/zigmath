<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArrearsReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No. Invoice',
            'Siswa',
            'Paket',
            'Periode',
            'Jatuh Tempo',
            'Tagihan',
            'Terbayar',
            'Sisa Tagihan',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->invoice_no,
            $row->student->name ?? '-',
            $row->student->package->name ?? '-',
            $row->period,
            $row->due_date?->format('d M Y'),
            $row->amount,
            $row->paid_amount,
            $row->remaining_balance,
            match ($row->status) {
                'unpaid' => 'Belum Bayar',
                'partial' => 'Cicilan',
                'overdue' => 'Terlambat',
                'paid' => 'Lunas',
                default => ucfirst($row->status),
            },
        ];
    }
}
