<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Data Siswa --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">📋 Data Siswa</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nama Siswa</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Kelas</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $record->class_type === 'regular' ? '📚 Reguler' : '👤 Private' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Paket</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->package->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Jatuh Tempo</p>
                    <p class="font-medium text-gray-900 dark:text-white">Tanggal {{ $record->due_day }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Orang Tua</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->parent_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">No HP</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->parent_phone }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Alamat</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->address ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="flex gap-3">
            <a href="{{ StudentResource::getUrl('invoices', ['record' => $record]) }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                📄 Lihat Tagihan
            </a>
            <a href="{{ StudentResource::getUrl('edit', ['record' => $record]) }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                ✏️ Edit Data
            </a>
        </div>
    </div>
</x-filament-panels::page>
