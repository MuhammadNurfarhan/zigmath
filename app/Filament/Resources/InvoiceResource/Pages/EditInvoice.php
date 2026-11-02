<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $amount = (float) ($data['amount'] ?? $this->record->amount);
        $discount = (float) ($data['discount'] ?? 0);
        $paidAmount = (float) ($this->record->paid_amount ?? 0);

        $net = max(0, $amount - $discount);
        $data['remaining_balance'] = max(0, $net - $paidAmount);

        if ($data['remaining_balance'] <= 0) {
            $data['status'] = 'paid';
        } elseif ($paidAmount > 0) {
            $data['status'] = 'partial';
        }

        return $data;
    }
}
