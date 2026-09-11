<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Bulan</label>

                <select wire:model.change="month"
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @foreach ($this->getMonthOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Tahun</label>

                <select wire:model.change="year"
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @foreach ($this->getYearOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Siswa</th>
                    <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Hadir</th>
                    <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Izin</th>
                    <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Sakit</th>
                    <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Alpa</th>
                    <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Total</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->students as $student)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $student->name }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full
                                bg-emerald-100 text-emerald-700
                                dark:bg-emerald-900/50 dark:text-emerald-300">
                                {{ $student->hadir_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full
                                bg-blue-100 text-blue-700
                                dark:bg-blue-900/50 dark:text-blue-300">
                                {{ $student->izin_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full
                                bg-amber-100 text-amber-700
                                dark:bg-amber-900/50 dark:text-amber-300">
                                {{ $student->sakit_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full
                                bg-rose-100 text-rose-700
                                dark:bg-rose-900/50 dark:text-rose-300">
                                {{ $student->alpa_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">
                            {{ $student->hadir_count + $student->izin_count + $student->sakit_count + $student->alpa_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data siswa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
