<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Jadwal Belajar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Jadwal Belajar')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Siswa')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('tutor_name')
                            ->label('Nama Tutor')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('day_of_week')
                            ->label('Hari')
                            ->options(Schedule::getDayOptions())
                            ->required(),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->seconds(false)
                            ->required()
                            ->rules([
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    // Pastikan field lain sudah diisi sebelum cek bentrok
                                    if (! $get('student_id') || ! $get('tutor_name') || ! $get('day_of_week') || ! $get('start_time')) {
                                        return;
                                    }

                                    // Buat instance temporary untuk cek bentrok
                                    $tempSchedule = new Schedule([
                                        'student_id' => $get('student_id'),
                                        'tutor_name' => $get('tutor_name'),
                                        'day_of_week' => $get('day_of_week'),
                                        'start_time' => $get('start_time'),
                                        'end_time' => $value,
                                    ]);

                                    // Cek bentrok menggunakan method dari Model Anda
                                    if ($tempSchedule->hasConflict($record?->id)) {
                                        $fail($tempSchedule->getConflictDetails() ?? 'Jadwal bentrok dengan jadwal lain.');
                                    }
                                },
                            ]),

                        Forms\Components\TextInput::make('room')
                            ->label('Ruangan')
                            ->maxLength(50)
                            ->nullable(),

                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Berulang Setiap Minggu')
                            ->default(true),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->nullable(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'cancelled' => 'Dibatalkan',
                                'completed' => 'Selesai',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Schedule::query()
                    ->with(['student'])
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tutor_name')
                    ->label('Tutor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => Schedule::getDayOptions()[$state] ?? '-'),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai'),

                Tables\Columns\TextColumn::make('room')
                    ->label('Ruangan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'cancelled' => 'Dibatalkan',
                        'completed' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'secondary',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Hari')
                    ->options(Schedule::getDayOptions()),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'cancelled' => 'Dibatalkan',
                        'completed' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('day_of_week');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
