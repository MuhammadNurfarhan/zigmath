<?php

namespace App\Filament\Resources\StudentResource;

use App\Models\Student;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StudentExporter extends Exporter
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('Nama Siswa'),
            ExportColumn::make('class_type')->label('Kelas'),
            ExportColumn::make('package.name')->label('Paket'),
            ExportColumn::make('parent_name')->label('Orang Tua'),
            ExportColumn::make('parent_phone')->label('No HP'),
            ExportColumn::make('school')->label('Sekolah'),
            ExportColumn::make('school_grade')->label('Kelas Sekolah'),
            ExportColumn::make('subject')->label('Mapel'),
            ExportColumn::make('address')->label('Alamat'),
            ExportColumn::make('due_day')->label('Jatuh Tempo'),
            ExportColumn::make('join_date')->label('Tanggal Gabung'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('created_at')->label('Dibuat'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export siswa berhasil! ' . number_format($export->successful_rows) . ' baris diekspor.';
        if ($export->failed_rows > 0) {
            $body .= ' ' . number_format($export->failed_rows) . ' baris gagal.';
        }
        return $body;
    }
}
