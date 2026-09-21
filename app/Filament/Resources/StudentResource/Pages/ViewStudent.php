<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_invoices')
                ->label('Lihat Tagihan')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => StudentResource::getUrl('invoices', ['record' => $this->record])),

            EditAction::make()
                ->label('Edit Data'),

            DeleteAction::make()
                ->label('Hapus'),
        ];
    }
}
