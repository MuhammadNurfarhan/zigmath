<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IncomeReportExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Tanggal Pembayaran',
            'No. Pembayaran',
            'No. Invoice',
            'Siswa',
            'Metode',
            'Nominal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->paid_at?->format('d M Y H:i'),
            $row->payment_no,
            $row->invoice->invoice_no ?? '-',
            $row->invoice->student->name ?? '-',
            match ($row->method) {
                'cash' => 'Tunai',
                'transfer' => 'Transfer',
                'qris' => 'QRIS',
                'e_wallet' => 'E-Wallet',
                default => 'Lainnya',
            },
            $row->amount,
        ];
    }
}
