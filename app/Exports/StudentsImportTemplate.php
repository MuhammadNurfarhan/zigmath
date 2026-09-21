<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * Data yang akan ditulis ke Excel.
     * Baris pertama = header (handled by WithHeadings)
     * Baris berikutnya = sample data
     */
    public function array(): array
    {
        return [
            [
                'Budi Santoso',
                'Reguler',
                'Reguler Bulanan SD',
                'Ahmad Santoso',
                '081234567890',
                'SD Negeri 1 Jakarta',
                'Kelas 5',
                'Jl. Merdeka No. 10, Jakarta',
                5,
                '2026-01-15',
                'Aktif',
            ],
            [
                'Siti Aminah',
                'Private',
                'Private 8 Sesi SMP',
                'Budi Raharjo',
                '082345678901',
                'SMP Harapan Bangsa',
                'Kelas 8',
                'Jl. Sudirman No. 25',
                10,
                '2026-02-01',
                'Aktif',
            ],
            [
                'Rina Wati',
                'Reguler',
                'Reguler Bulanan SMP',
                'Dewi Wati',
                '083456789012',
                'SMP Negeri 3 Surabaya',
                'Kelas 7',
                'Jl. Pemuda No. 88, Surabaya',
                15,
                '2026-03-01',
                'Aktif',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'Kelas (Reguler/Private)',
            'Paket',
            'Orang Tua',
            'No HP',
            'Sekolah',
            'Kelas Sekolah',
            'Alamat',
            'Jatuh Tempo (1-28)',
            'Tanggal Gabung (YYYY-MM-DD)',
            'Status (Aktif/Nonaktif/Cuti)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, // Nama Siswa
            'B' => 18, // Kelas
            'C' => 25, // Paket
            'D' => 22, // Orang Tua
            'E' => 18, // No HP
            'F' => 25, // Sekolah
            'G' => 15, // Kelas Sekolah
            'H' => 30, // Alamat
            'I' => 15, // Jatuh Tempo
            'J' => 22, // Tanggal Gabung
            'K' => 25, // Status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row (baris 1)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F46E5'], // Indigo
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // Style sample rows (baris 2, 3, 4) - italic & gray agar user tahu ini contoh
        $sampleStyle = [
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF6B7280'],
            ],
        ];

        return [
            1 => $headerStyle,
            2 => $sampleStyle,
            3 => $sampleStyle,
            4 => $sampleStyle,
        ];
    }
}
