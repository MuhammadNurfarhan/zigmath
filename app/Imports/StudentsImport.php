<?php

namespace App\Imports;

use App\Models\Package;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public array $summary = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Row 1 = heading, mulai dari 2

        foreach ($rows as $row) {
            $rowNumber++;

            try {
                // Skip baris kosong
                if (empty($row['nama_siswa'])) {
                    continue;
                }

                // Resolve Paket dari nama
                $package = Package::where('name', 'LIKE', '%'.trim($row['paket']).'%')->first();
                if (! $package) {
                    throw ValidationException::withMessages([
                        "row_{$rowNumber}" => "Paket '{$row['paket']}' tidak ditemukan di sistem.",
                    ]);
                }

                // Cek duplikat (nama + no HP ortu)
                $exists = Student::where('name', trim($row['nama_siswa']))
                    ->where('parent_phone', trim($row['no_hp']))
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        "row_{$rowNumber}" => "Duplikat: {$row['nama_siswa']} sudah terdaftar.",
                    ]);
                }

                // Normalisasi nilai
                $classType = strtolower(trim($row['kelas'] ?? ''));
                if (! in_array($classType, ['regular', 'private', 'reguler'])) {
                    throw ValidationException::withMessages([
                        "row_{$rowNumber}" => "Kelas harus 'Reguler' atau 'Private'.",
                    ]);
                }
                $classType = ($classType === 'reguler') ? 'regular' : $classType;

                $status = strtolower(trim($row['status'] ?? 'active'));
                if (! in_array($status, ['active', 'inactive', 'cuti', 'aktif', 'nonaktif'])) {
                    $status = 'active';
                }
                if ($status === 'aktif') {
                    $status = 'active';
                }
                if ($status === 'nonaktif') {
                    $status = 'inactive';
                }

                // Parse tanggal join (YYYY-MM-DD atau DD-MM-YYYY)
                $joinDate = null;
                if (! empty($row['tanggal_gabung'])) {
                    try {
                        $joinDate = Carbon::parse($row['tanggal_gabung'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $joinDate = null;
                    }
                }

                Student::create([
                    'name' => trim($row['nama_siswa']),
                    'class_type' => $classType,
                    'package_id' => $package->id,
                    'parent_name' => trim($row['orang_tua']),
                    'parent_phone' => trim($row['no_hp']),
                    'school' => $row['sekolah'] ?? null,
                    'school_grade' => $row['kelas_sekolah'] ?? null,
                    'subject' => $row['mapel'] ?? null,
                    'address' => $row['alamat'] ?? null,
                    'due_day' => (int) ($row['jatuh_tempo'] ?? 1),
                    'join_date' => $joinDate,
                    'status' => $status,
                    'created_by' => auth()->id(),
                ]);

                $this->summary['success']++;
            } catch (ValidationException $e) {
                $this->summary['failed']++;
                $this->summary['errors'][] = [
                    'row' => $rowNumber,
                    'reason' => array_values($e->errors())[0][0] ?? 'Unknown error',
                ];
            } catch (\Exception $e) {
                $this->summary['failed']++;
                $this->summary['errors'][] = [
                    'row' => $rowNumber,
                    'reason' => $e->getMessage(),
                ];
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama_siswa' => 'required|string|max:100',
            'kelas' => 'required|string',
            'paket' => 'required|string',
            'orang_tua' => 'required|string|max:100',
            'no_hp' => ['required', 'regex:/^(08|\+62)\d{8,12}$/'],
            'jatuh_tempo' => 'required|integer|between:1,28',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama_siswa.required' => 'Nama siswa wajib diisi.',
            'kelas.required' => 'Kelas wajib diisi (Reguler/Private).',
            'paket.required' => 'Paket wajib diisi.',
            'orang_tua.required' => 'Nama orang tua wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format HP: 08xxxxxxxxxx atau +62xxxxxxxxxx',
            'jatuh_tempo.between' => 'Jatuh tempo harus antara 1–28.',
        ];
    }
}
