<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class DueDateWidget extends Widget
{
    protected static string $view = 'filament.widgets.due-date-widget';

    protected static ?int $sort = 3;

    protected static ?string $heading = '⚠️ Tagihan Mendekati Jatuh Tempo';

    public function getDueInvoices(): Collection
    {
        return Invoice::with('student')
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }
}
