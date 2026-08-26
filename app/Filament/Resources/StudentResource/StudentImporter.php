<?php

namespace App\Filament\Resources\StudentResource;

use App\Models\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->label('Nama Siswa')->requiredMapping()->rules(['required', 'max:100']),
            ImportColumn::make('class_type')->label('Kelas (regular/private)')->requiredMapping()->rules(['required', 'in:regular,private']),
            ImportColumn::make('package')->label('Nama Paket (harus sudah ada di sistem)')
                ->relationship(resolveUsing: fn(string $state) => \App\Models\Package::where('name', $state)->first())
                ->requiredMapping(),
            ImportColumn::make('parent_name')->label('Nama Orang Tua')->requiredMapping()->rules(['required', 'max:100']),
            ImportColumn::make('parent_phone')->label('No HP Orang Tua')->requiredMapping()->rules(['required', 'regex:/^(08|\+62)\d{8,12}$/']),
            ImportColumn::make('school')->label('Sekolah'),
            ImportColumn::make('school_grade')->label('Kelas Sekolah'),
            ImportColumn::make('subject')->label('Mata Pelajaran'),
            ImportColumn::make('address')->label('Alamat'),
            ImportColumn::make('due_day')->label('Tanggal Jatuh Tempo (1-28)')->requiredMapping()->rules(['required', 'integer', 'between:1,28']),
            ImportColumn::make('join_date')->label('Tanggal Gabung (YYYY-MM-DD)')->rules(['nullable', 'date']),
            ImportColumn::make('status')->label('Status')->rules(['nullable', 'in:active,inactive,cuti'])->default('active'),
        ];
    }

    public function resolveRecord(): ?Student
    {
        // Cek duplikat berdasarkan nama + HP orang tua
        return Student::firstOrNew([
            'name' => $this->data['name'],
            'parent_phone' => $this->data['parent_phone'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import siswa selesai! ' . number_format($import->successful_rows) . ' baris berhasil.';
        if ($import->failed_rows > 0) {
            $body .= ' ' . number_format($import->failed_rows) . ' baris gagal.';
        }
        return $body;
    }
}
