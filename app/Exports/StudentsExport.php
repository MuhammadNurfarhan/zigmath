<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        return Student::query()->with('package');
    }

    public function headings(): array
    {
        return [
            'ID', 'Nama Siswa', 'Kelas', 'Paket', 'Orang Tua',
            'No HP', 'Sekolah', 'Kelas Sekolah', 'Mapel', 'Alamat',
            'Jatuh Tempo', 'Tanggal Gabung', 'Status', 'Dibuat',
        ];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->name,
            $student->class_type === 'regular' ? 'Reguler' : 'Private',
            $student->package?->name ?? '-',
            $student->parent_name,
            $student->parent_phone,
            $student->school ?? '-',
            $student->school_grade ?? '-',
            $student->subject ?? '-',
            $student->address ?? '-',
            'Tgl '.$student->due_day,
            $student->join_date?->format('d-m-Y') ?? '-',
            match ($student->status) {
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                'cuti' => 'Cuti',
                default => $student->status,
            },
            $student->created_at->format('d-m-Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
