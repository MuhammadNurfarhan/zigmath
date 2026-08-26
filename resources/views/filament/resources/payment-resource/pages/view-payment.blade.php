<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Invoice Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">💳 Detail Pembayaran</h3>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    {{ match($record->method) {
                        'cash' => 'bg-green-100 text-green-800',
                        'transfer' => 'bg-blue-100 text-blue-800',
                        'qris' => 'bg-purple-100 text-purple-800',
                        'e_wallet' => 'bg-orange-100 text-orange-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">
                    {{ strtoupper($record->method) }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Nomor Pembayaran</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->payment_no }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Nomor Invoice</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->invoice->invoice_no ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Jumlah Pembayaran</p>
                    <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($record->amount, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tanggal Pembayaran</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $record->paid_at->format('d F Y, H:i') }}
                    </p>
                </div>
                @if($record->reference_number)
                <div>
                    <p class="text-sm text-gray-500">No. Referensi</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->reference_number }}</p>
                </div>
                @endif
                <div>
                    <p class="text-sm text-gray-500">Diverifikasi Oleh</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->verifier->name ?? '-' }}</p>
                </div>
            </div>

            @if($record->notes)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500">Keterangan</p>
                <p class="text-gray-900 dark:text-white">{{ $record->notes }}</p>
            </div>
            @endif

            @if($record->proof_path)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 mb-2">Bukti Pembayaran</p>
                <a href="{{ asset('storage/' . $record->proof_path) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Lihat Bukti Pembayaran
                </a>
            </div>
            @endif
        </div>

        {{-- Info Siswa --}}
        @if($record->invoice && $record->invoice->student)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">👤 Informasi Siswa</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nama Siswa</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->invoice->student->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Orang Tua</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->invoice->student->parent_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">No HP</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->invoice->student->parent_phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Kelas</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $record->invoice->student->class_type === 'regular' ? '📚 Reguler' : '👤 Private' }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Quick Actions --}}
        <div class="flex gap-3">
            <a href="{{ PaymentResource::getUrl('edit', ['record' => $record]) }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                ✏️ Edit Pembayaran
            </a>
            <a href="{{ PaymentResource::getUrl('index') }}"
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                ← Kembali
            </a>
        </div>
    </div>
</x-filament-panels::page>
