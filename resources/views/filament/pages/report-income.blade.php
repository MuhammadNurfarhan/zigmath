<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">
                Total Pemasukan Sesuai Filter
            </p>

            <p class="text-2xl font-bold text-emerald-600 mt-1">
                Rp {{ number_format($this->getFilteredTableQuery()->sum('amount'), 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">
                Jumlah Transaksi
            </p>

            <p class="text-2xl font-bold text-indigo-600 mt-1">
                {{ $this->getFilteredTableQuery()->count() }} Transaksi
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
