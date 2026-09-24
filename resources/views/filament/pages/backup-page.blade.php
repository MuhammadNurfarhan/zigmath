<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Backup Database Zigmath
        </h3>

        <p class="text-sm text-gray-500 mt-2">
            Sistem akan membuat backup database dalam format SQL dan menyimpannya di server.
        </p>

        <div class="mt-4">
            <x-filament::button wire:click="backup">
                Backup Sekarang
            </x-filament::button>
        </div>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Riwayat Backup
            </h3>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 text-left">File</th>
                    <th class="px-4 py-3 text-left">Ukuran</th>
                    <th class="px-4 py-3 text-left">Waktu</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->getBackups() as $backup)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $backup['name'] }}
                        </td>

                        <td class="px-4 py-3 text-gray-500">
                            {{ $backup['size'] }}
                        </td>

                        <td class="px-4 py-3 text-gray-500">
                            {{ $backup['time'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            Belum ada backup.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
