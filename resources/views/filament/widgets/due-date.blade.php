<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-3">
            @forelse($this->getDueInvoices() as $invoice)
                <div
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $invoice->student->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ $invoice->invoice_no }} • Due: {{ $invoice->due_date->format('d M Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-rose-600">
                            Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                        </p>
                        <span
                            class="text-xs px-2 py-0.5 rounded-full
                            {{ $invoice->status === 'overdue' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $invoice->status === 'overdue' ? 'Terlambat' : 'Segera Jatuh Tempo' }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-4">
                    ✅ Tidak ada tagihan yang mendekati jatuh tempo dalam 7 hari ke depan.
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
