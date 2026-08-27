<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\StudentExporter;
use App\Filament\Resources\StudentResource\StudentImporter;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

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
                    ->reactive(),

                Forms\Components\Select::make('package_id')
                    ->label('Paket Belajar')
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('class_type')
                    ->label('Kelas')
                    ->colors(['primary' => 'regular', 'warning' => 'private'])
                    ->formatStateUsing(fn(string $state): string =>
                        $state === 'regular' ? 'Reguler' : 'Private'
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
                    ->formatStateUsing(fn(int $state): string => "Tgl {$state}")
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'cuti',
                    ])
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'cuti' => 'Cuti',
                    }),

                Tables\Columns\TextColumn::make('invoices_sum_remaining_balance')
                    ->label('Tunggakan')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn(?float $state): string => ($state ?? 0) > 0 ? 'danger' : 'success'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('invoices')
                    ->label('📄 Tagihan')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Student $record): string =>
                        StudentResource::getUrl('invoices', ['record' => $record])
                    ),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(StudentExporter::class)
                    ->label('📥 Export Excel'),
                Tables\Actions\ImportAction::make()
                    ->importer(StudentImporter::class)
                    ->label('📤 Import Excel'),
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
