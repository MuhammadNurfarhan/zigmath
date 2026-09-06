<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">
                Total Tunggakan Sesuai Filter
            </p>

            <p class="text-2xl font-bold text-rose-600 mt-1">
                Rp {{ number_format($this->getFilteredTableQuery()->sum('remaining_balance'), 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">
                Jumlah Tagihan Bermasalah
            </p>

            <p class="text-2xl font-bold text-warning mt-1">
                {{ $this->getFilteredTableQuery()->count() }} Tagihan
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
