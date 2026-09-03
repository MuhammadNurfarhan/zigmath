<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $period = $data['period'] ?? now()->format('Y-m');

        // Ambil nomor invoice terbesar (string max) dari database, termasuk yang sudah di-soft delete
        $maxInvoiceNo = Invoice::withTrashed()
            ->where('period', $period)
            ->max('invoice_no');

        $seqNumber = 1;
        if ($maxInvoiceNo && preg_match('/(\d{4})$/', $maxInvoiceNo, $matches)) {
            $seqNumber = ((int) $matches[1]) + 1;
        }

        // 1. Generate nomor invoice baru
        $data['invoice_no'] = 'INV/ZGM/'.str_replace('-', '', $period).'/'.str_pad($seqNumber, 4, '0', STR_PAD_LEFT);

        // 2. Pastikan paid_amount selalu bernilai 0 jika tidak diisi di form
        if (! isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }

        // 3. Hitung remaining_balance secara akurat berdasarkan amount, discount, dan paid_amount
        $amount = $data['amount'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $paidAmount = $data['paid_amount'];

        if (! isset($data['remaining_balance'])) {
            $data['remaining_balance'] = $amount - $discount - $paidAmount;
        }

        return $data;
    }
}
