<x-filament-widgets::widget>
    <x-filament::section heading="📅 Jadwal Hari Ini">
        @forelse($schedules as $schedule)
            <div
                class="flex items-center justify-between py-3 border-b last:border-b-0 border-gray-200 dark:border-gray-700">

                {{-- Bagian Kiri: Info Siswa & Tutor --}}
                <div class="flex-1 min-w-0 mr-4">
                    <p class="text-sm text-gray-500 mt-0.5">
                        Mata Pelajaran: <span class="font-medium">{{ $schedule->subject?->name ?? '-' }}</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Tutor: <span class="font-medium">{{ $schedule->tutor_name }}</span>
                    </p>
                </div>

                {{-- Bagian Kanan: Waktu & Ruangan --}}
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        📍RUANGAN {{ $schedule->room ?? 'Ruangan belum diatur' }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">
                🎉 Tidak ada jadwal hari ini.
            </p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>
