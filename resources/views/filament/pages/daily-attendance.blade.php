<x-filament-panels::page>
    {{-- Form Filter --}}
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Tanggal --}}
            <div>
                <label
                    class="fi-input-wrappers-label block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-1">
                    Tanggal
                </label>
                <input type="date" wire:model.change="date"
                    class="fi-input block w-full rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm">
            </div>

            {{-- Jadwal --}}
            <div>
                <label
                    class="fi-input-wrappers-label block text-sm font-medium leading-6 text-gray-950 dark:text-white mb-1">
                    Jadwal
                </label>
                <select wire:model.change="schedule_id"
                    class="fi-select block w-full rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm">
                    <option value="" class="bg-white dark:bg-gray-800">Pilih Jadwal</option>
                    @foreach ($this->schedules as $schedule)
                        <option value="{{ $schedule->id }}" class="bg-white dark:bg-gray-800">
                            {{ $schedule->start_time }} - {{ $schedule->end_time }}
                            • {{ $schedule->tutor_name }}
                            • {{ $schedule->students->count() }} siswa
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if ($this->selectedSchedule)
        {{-- Tabel Absensi --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

            {{-- Header --}}
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Daftar Siswa
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->selectedSchedule->start_time }} - {{ $this->selectedSchedule->end_time }}
                        • Tutor: {{ $this->selectedSchedule->tutor_name }}
                    </p>
                </div>

                <x-filament::button icon="heroicon-m-check-circle" color="success" size="md"
                    wire:click="markAllPresent" wire:loading.attr="disabled">
                    Tandai Semua Hadir
                </x-filament::button>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th
                                class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                                Siswa
                            </th>
                            <th
                                class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                                Status Kehadiran
                            </th>
                            <th
                                class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                                Keterangan
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach ($this->selectedSchedule->students as $student)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">

                                {{-- Nama Siswa --}}
                                <td class="fi-ta-cell px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-950 dark:text-white">
                                                {{ $student->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="fi-ta-cell px-4 py-3 whitespace-nowrap">
                                    <select wire:model="attendances.{{ $student->id }}.status"
                                        class="fi-select block w-full rounded-lg border-0 py-1.5 px-3 text-sm text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500 bg-white dark:bg-white/5">
                                        <option value="hadir" class="bg-white dark:bg-gray-800">✅ Hadir</option>
                                        <option value="izin" class="bg-white dark:bg-gray-800">📝 Izin</option>
                                        <option value="sakit" class="bg-white dark:bg-gray-800">🤒 Sakit</option>
                                        <option value="alpa" class="bg-white dark:bg-gray-800">❌ Alpa</option>
                                    </select>
                                </td>

                                {{-- Keterangan --}}
                                <td class="fi-ta-cell px-4 py-3">
                                    <input type="text" wire:model.defer="attendances.{{ $student->id }}.notes"
                                        placeholder="Tambahkan keterangan..."
                                        class="fi-input block w-full rounded-lg border-0 py-1.5 px-3 text-sm text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500 bg-white dark:bg-white/5">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer Actions --}}
            <div
                class="flex items-center justify-between gap-3 px-4 py-4 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span
                        class="font-medium text-gray-950 dark:text-white">{{ $this->selectedSchedule->students->count() }}</span>
                    siswa
                </p>

                <x-filament::button icon="heroicon-m-check" size="md" wire:click="save"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        Simpan Absensi
                    </span>
                    <span wire:loading wire:target="save">
                        Menyimpan...
                    </span>
                </x-filament::button>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12">
            <div class="mx-auto max-w-md text-center">
                <x-filament::icon icon="heroicon-o-calendar-days" class="mx-auto text-gray-400 dark:text-gray-500" />
                <h3 class="mt-4 text-sm font-semibold text-gray-950 dark:text-white">
                    Belum Ada Jadwal Dipilih
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Pilih tanggal dan jadwal pada form di atas untuk mulai mengisi absensi siswa.
                </p>
            </div>
        </div>
    @endif
</x-filament-panels::page>
