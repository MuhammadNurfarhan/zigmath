<x-filament-panels::page>
    {{-- Header Info Siswa --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">
                        {{ strtoupper(substr($this->getRecord()->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getRecord()->name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->getRecord()->class_type === 'regular' ? '📚 Reguler' : '👤 Private' }}
                        • {{ $this->getRecord()->package->name ?? '-' }}
                        • Jatuh Tempo: Tgl {{ $this->getRecord()->due_day }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Orang Tua</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $this->getRecord()->parent_name }}</p>
                <p class="text-sm text-indigo-600">{{ $this->getRecord()->parent_phone }}</p>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Tagihan</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                Rp {{ number_format($this->getRecord()->invoices->sum('amount'), 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Terbayar</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">
                Rp {{ number_format($this->getRecord()->invoices->sum('paid_amount'), 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Sisa Tunggakan</p>
            <p class="text-2xl font-bold text-rose-600 mt-1">
                Rp {{ number_format($this->getRecord()->invoices->sum('remaining_balance'), 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
            <p class="text-2xl font-bold mt-1 {{ $this->getRecord()->invoices->sum('remaining_balance') > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $this->getRecord()->invoices->sum('remaining_balance') > 0 ? '⚠️ Ada Tunggakan' : '✅ Lunas Semua' }}
            </p>
        </div>
    </div>

    {{-- Tabel Angsuran --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                📋 Tabel Angsuran Bulanan
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Klik tombol "💰 Input Bayar" untuk mencatat pembayaran
            </p>
        </div>
        <div class="p-4">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
