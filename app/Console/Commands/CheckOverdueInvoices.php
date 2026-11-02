<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'zigmath:check-overdue';

    protected $description = 'Update status invoice yang sudah lewat jatuh tempo';

    public function handle(): int
    {
        $overdueCount = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', '<', today())
            ->where('remaining_balance', '>', 0)
            ->update(['status' => 'overdue']);

        $this->info("✅ {$overdueCount} invoice diupdate menjadi overdue.");

        return self::SUCCESS;
    }
}
