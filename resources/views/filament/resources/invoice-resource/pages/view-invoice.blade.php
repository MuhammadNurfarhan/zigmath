@php
    use App\Filament\Resources\InvoiceResource;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Invoice Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📄 Detail Tagihan</h3>
                <span
                    class="px-3 py-1 text-sm font-medium rounded-full
                    {{ match ($record->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'partial' => 'bg-yellow-100 text-yellow-800',
                        'overdue' => 'bg-red-100 text-red-800',
                        'unpaid' => 'bg-gray-100 text-gray-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">
                    {{ match ($record->status) {
                        'paid' => '✅ LUNAS',
                        'partial' => '⏳ CICILAN',
                        'overdue' => '️ TERLAMBAT',
                        'unpaid' => '⏰ BELUM BAYAR',
                        default => strtoupper($record->status),
                    } }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Nomor Invoice</p>
                    <p class="font-medium text-gray-900 dark:text-white text-lg">{{ $record->invoice_no }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Periode</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($record->period . '-01')->translatedFormat('F Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tanggal Jatuh Tempo</p>
                    <p
                        class="font-medium {{ $record->isOverdue() ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                        {{ $record->due_date->format('d F Y') }}
                        @if ($record->isOverdue())
                            <span class="text-xs">(Terlambat {{ now()->diffInDays($record->due_date) }} hari)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ match ($record->status) {
                            'paid' => 'Lunas',
                            'partial' => 'Cicilan',
                            'overdue' => 'Terlambat',
                            'unpaid' => 'Belum Bayar',
                            default => $record->status,
                        } }}
                    </p>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Tagihan</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($record->amount, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Terbayar</p>
                        <p class="text-xl font-bold text-emerald-600">
                            Rp {{ number_format($record->paid_amount, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Sisa Tagihan</p>
                        <p class="text-xl font-bold text-rose-600">
                            Rp {{ number_format($record->remaining_balance, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if ($record->notes)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500">Catatan</p>
                    <p class="text-gray-900 dark:text-white">{{ $record->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Info Siswa --}}
        @if ($record->student)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">👤 Informasi Siswa</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama Siswa</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $record->student->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Paket</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $record->student->package->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Orang Tua</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $record->student->parent_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">No HP</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $record->student->parent_phone }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Alamat</p>
                        <p class="text-gray-900 dark:text-white">{{ $record->student->address ?? '-' }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Riwayat Pembayaran --}}
        @if ($record->payments->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">💰 Riwayat Pembayaran
                    ({{ $record->payments->count() }}x)</h3>
                <div class="space-y-3">
                    @foreach ($record->payments as $payment)
                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $payment->paid_at->format('d M Y, H:i') }}
                                    </p>
                                    @if ($payment->reference_number)
                                        <p class="text-xs text-gray-400 mt-1">Ref: {{ $payment->reference_number }}</p>
                                    @endif
                                </div>
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full
                            {{ match ($payment->method) {
                                'cash' => 'bg-green-100 text-green-800',
                                'transfer' => 'bg-blue-100 text-blue-800',
                                'qris' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800',
                            } }}">
                                    {{ strtoupper($payment->method) }}
                                </span>
                            </div>
                            @if ($payment->notes)
                                <p class="text-xs text-gray-400 mt-2">📝 {{ $payment->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="flex gap-3">
            <a href="{{ InvoiceResource::getUrl('index') }}"
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                ← Kembali
            </a>
        </div>
    </div>
</x-filament-panels::page>
