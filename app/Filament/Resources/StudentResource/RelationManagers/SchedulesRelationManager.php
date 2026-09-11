<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Jadwal Belajar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tutor_name')
                    ->label('Nama Tutor')
                    ->required(),

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
                    ->required(),

                Forms\Components\TextInput::make('room')
                    ->label('Ruangan'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'cancelled' => 'Dibatalkan',
                        'completed' => 'Selesai',
                    ])
                    ->default('active'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => Schedule::getDayOptions()[$state] ?? '-'),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai'),

                Tables\Columns\TextColumn::make('tutor_name')
                    ->label('Tutor'),

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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
