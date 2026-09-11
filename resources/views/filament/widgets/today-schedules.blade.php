<x-filament-widgets::widget>
    <x-filament::section heading="📅 Jadwal Hari Ini">
        @forelse($schedules as $schedule)
            <div
                class="flex items-center justify-between py-3 border-b last:border-b-0 border-gray-200 dark:border-gray-700">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $schedule->student->name ?? '-' }}
                    </p>

                    <p class="text-sm text-gray-500">
                        Tutor: {{ $schedule->tutor_name }}
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-sm font-semibold text-indigo-600">
                        {{ $schedule->start_time }} - {{ $schedule->end_time }}
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $schedule->room ?? 'Ruangan belum diatur' }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                Tidak ada jadwal hari ini.
            </p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>
