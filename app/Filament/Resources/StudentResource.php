<?php

namespace App\Filament\Resources;

use App\Exports\StudentsExport;
use App\Filament\Resources\StudentResource\Pages;
use App\Imports\StudentsImport;
use App\Models\Package;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Siswa';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Siswa')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Siswa')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('class_type')
                    ->label('Kelas')
                    ->options([
                        'regular' => '📚 Reguler',
                        'private' => '👤 Private',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('package_id', null)),

                Forms\Components\Select::make('package_id')
                    ->label('Paket Belajar')
                    ->options(function (Get $get) {
                        $classType = $get('class_type');

                        // Jika kelas belum dipilih, kembalikan array kosong
                        if (! $classType) {
                            return [];
                        }

                        return Package::where('is_active', true)
                            ->where('type', $classType)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (Get $get) => ! $get('class_type'))
                    ->placeholder(fn (Get $get) => ! $get('class_type') ? '⚠️ Pilih Kelas terlebih dahulu' : '-- Pilih Paket Belajar --')
                    ->helperText(fn (Get $get) => ! $get('class_type') ? 'Silakan pilih kelas untuk mengaktifkan kolom ini.' : 'Menampilkan paket sesuai kelas yang dipilih.'),

                Forms\Components\TextInput::make('school')
                    ->label('Sekolah')
                    ->nullable(),

                Forms\Components\TextInput::make('school_grade')
                    ->label('Kelas Sekolah')
                    ->placeholder('Contoh: SD Kelas 5')
                    ->nullable(),

                Forms\Components\TextInput::make('subject')
                    ->label('Mata Pelajaran')
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Data Orang Tua / Wali')->schema([
                Forms\Components\TextInput::make('parent_name')
                    ->label('Nama Orang Tua / Wali')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('parent_phone')
                    ->label('Nomor HP Orang Tua')
                    ->tel()
                    ->required()
                    ->regex('/^(08|\+62)\d{8,12}$/')
                    ->placeholder('08xxxxxxxxxx')
                    ->helperText('Format: 08xxxxxxxxxx'),

                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Pengaturan Tagihan')->schema([
                Forms\Components\Select::make('due_day')
                    ->label('Tanggal Jatuh Tempo')
                    ->options(array_combine(range(1, 28), range(1, 28)))
                    ->required()
                    ->rules(['integer', 'between:1,28'])
                    ->helperText('Tagihan akan jatuh tempo setiap tanggal ini'),

                Forms\Components\DatePicker::make('join_date')
                    ->label('Tanggal Bergabung')
                    ->default(now())
                    ->nullable(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => '🟢 Aktif',
                        'inactive' => '🔴 Nonaktif',
                        'cuti' => '🟡 Cuti',
                    ])
                    ->default('active')
                    ->required(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('invoices', 'remaining_balance')
                ->withSum('invoices', 'paid_amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('class_type')
                    ->label('Kelas')
                    ->colors(['primary' => 'regular', 'warning' => 'private'])
                    ->formatStateUsing(fn (string $state): string => $state === 'regular' ? 'Reguler' : 'Private'
                    ),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket')
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent_name')
                    ->label('Orang Tua')
                    ->searchable(),

                Tables\Columns\TextColumn::make('parent_phone')
                    ->label('No HP')
                    ->copyable()
                    ->copyMessage('Nomor disalin!'),

                Tables\Columns\TextColumn::make('due_day')
                    ->label('Jatuh Tempo')
                    ->formatStateUsing(fn (int $state): string => "Tgl {$state}")
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'cuti',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'cuti' => 'Cuti',
                    }),

                Tables\Columns\TextColumn::make('invoices_sum_remaining_balance')
                    ->label('Tunggakan')
                    ->money('IDR')
                    ->sortable()
                    ->searchable(false)
                    ->color(fn (?float $state): string => ($state ?? 0) > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('invoices_sum_paid_amount')
                    ->label('Total Terbayar')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('class_type')
                    ->label('Kelas')
                    ->options(['regular' => 'Reguler', 'private' => 'Private']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif', 'cuti' => 'Cuti']),
                Tables\Filters\SelectFilter::make('package_id')
                    ->label('Paket')
                    ->relationship('package', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Action::make('invoices')
                    ->label('Tagihan')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Student $record): string => StudentResource::getUrl('invoices', ['record' => $record])
                    ),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                // ==========================================
                // 📄 DOWNLOAD TEMPLATE (ACTION TERPISAH)
                // ==========================================
                Action::make('download_template')
                    ->label('📄 Template Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->url(route('students.download-template'), shouldOpenInNewTab: true)
                    ->tooltip('Download template Excel untuk import'),

                // ==========================================
                // 📥 EXPORT EXCEL
                // ==========================================
                Action::make('export_excel')
                    ->label('📥 Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (HasTable $livewire) {

                        $filteredQuery = $livewire->getFilteredSortedTableQuery()->with('package');

                        return Excel::download(
                            new StudentsExport($filteredQuery),
                            'zigmath-siswa-'.now()->format('Y-m-d_His').'.xlsx'
                        );
                    }),

                // ==========================================
                // 📤 IMPORT EXCEL (TANPA ACTION DI FORM)
                // ==========================================
                Action::make('import_excel')
                    ->label('📤 Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading('Import Data Siswa dari Excel')
                    ->modalDescription('Upload file Excel (.xlsx) yang berisi data siswa. Pastikan format sesuai template.')
                    ->modalWidth('lg')
                    ->form([
                        // ✅ SEKARANG HANYA BERISI KOMPONEN FORM
                        FileUpload::make('file')
                            ->label('Upload File Excel (.xlsx / .csv)')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->disk('local')
                            ->directory('imports/students')
                            ->maxSize(10240)
                            ->required()
                            ->helperText('Maksimal 10 MB. Download template dulu lewat tombol "Template Excel" di atas.'),
                    ])
                    ->action(function (array $data): void {
                        $import = new StudentsImport;

                        try {
                            Excel::import(
                                $import,
                                Storage::disk('local')->path($data['file'])
                            );
                        } catch (ValidationException $e) {
                            $failures = $e->failures();
                            $errors = [];
                            foreach (array_slice($failures, 0, 10) as $failure) {
                                $errors[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
                            }

                            Notification::make()
                                ->title('⚠️ Import Gagal')
                                ->body(
                                    'Terdapat '.count($failures).' error validasi. Contoh: '
                                    .implode(' | ', $errors)
                                )
                                ->danger()
                                ->duration(10000)
                                ->send();

                            Storage::disk('local')->delete($data['file']);

                            return;
                        }

                        // Cleanup file upload
                        Storage::disk('local')->delete($data['file']);

                        // Notifikasi ringkasan
                        $summary = $import->summary;
                        $body = "✅ Berhasil: {$summary['success']} siswa. ";
                        if ($summary['failed'] > 0) {
                            $body .= "❌ Gagal: {$summary['failed']} siswa.";
                        } else {
                            $body .= 'Tidak ada error.';
                        }

                        Notification::make()
                            ->title('Import Selesai!')
                            ->body($body)
                            ->success()
                            ->duration(8000)
                            ->send();

                        // Detail error jika ada
                        if ($summary['failed'] > 0 && ! empty($summary['errors'])) {
                            $errorList = collect($summary['errors'])
                                ->take(15)
                                ->map(fn ($err) => "Baris {$err['row']}: {$err['reason']}")
                                ->implode('<br>');

                            Notification::make()
                                ->title('📋 Detail Error Import')
                                ->body($errorList)
                                ->warning()
                                ->duration(15000)
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'invoices' => Pages\StudentInvoices::route('/{record}/invoices'),
        ];
    }
}
