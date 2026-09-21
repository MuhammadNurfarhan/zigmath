<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                        Forms\Components\Select::make('students')
                            ->label('Siswa')
                            ->placeholder('Pilih Siswa')
                            ->relationship('students', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules([
                                function (Get $get, Component $component) {
                                    return function (string $attribute, $value, Closure $fail) use ($get, $component) {
                                        if (empty($value)) {
                                            return;
                                        }

                                        $day = $get('day_of_week');
                                        $start = $get('start_time');
                                        $end = $get('end_time');

                                        if (! $day || ! $start || ! $end) {
                                            return;
                                        }

                                        $record = $component->getRecord();

                                        // Cek bentrok siswa
                                        $query = Schedule::query()
                                            ->where('day_of_week', $day)
                                            ->where('start_time', '<', $end)
                                            ->where('end_time', '>', $start)
                                            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $value));

                                        // PENTING: Kecualikan record saat ini jika sedang mode EDIT
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }

                                        if ($query->exists()) {
                                            $fail('Salah satu siswa yang dipilih sudah memiliki jadwal yang bentrok di hari dan jam tersebut.');
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\TextInput::make('tutor_name')
                            ->label('Nama Tutor')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->required(),

                        Forms\Components\Select::make('day_of_week')
                            ->label('Hari')
                            ->options(Schedule::getDayOptions()) // Pastikan method ini ada di Model
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
                                function (Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get) {
                                        $start = $get('start_time');

                                        // Validasi dasar: Jam selesai harus lebih besar dari jam mulai
                                        if ($start && $value <= $start) {
                                            $fail('Jam selesai harus lebih besar dari jam mulai.');

                                            return;
                                        }
                                    };
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
                    ->with(['students']) // FIX: dari 'student' menjadi 'students'
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tutor_name')
                    ->label('Tutor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('students.name')
                    ->label('Siswa')
                    ->badge()
                    ->limit(3)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Jumlah Siswa')
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => Schedule::getDayOptions()[$state] ?? '-'),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state)->format('H:i')),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state)->format('H:i')),

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

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                // FIX: Filter siswa disesuaikan dengan relasi many-to-many
                Tables\Filters\SelectFilter::make('students')
                    ->label('Siswa')
                    ->relationship('students', 'name')
                    ->multiple()
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
