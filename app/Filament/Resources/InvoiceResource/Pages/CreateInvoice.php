<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Setting;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $period = $data['period'] ?? now()->format('Y-m');

        // 1. Generate nomor invoice baru menggunakan method terpusat
        $data['invoice_no'] = Invoice::generateInvoiceNumber($period);

        // 2. Pastikan paid_amount selalu bernilai 0 jika tidak diisi di form
        if (! isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }

        // 3. Hitung remaining_balance secara akurat berdasarkan amount, discount, dan paid_amount
        $amount = (float) ($data['amount'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $paidAmount = (float) $data['paid_amount'];

        if (! isset($data['remaining_balance'])) {
            $data['remaining_balance'] = max(0, $amount - $discount - $paidAmount);
        }

        return $data;
    }
}
