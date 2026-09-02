<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Paket Belajar';

    protected static ?string $modelLabel = 'Paket';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Paket')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Paket')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Contoh: Reguler Bulanan SD'),

                Forms\Components\Select::make('type')
                    ->label('Tipe Kelas')
                    ->options([
                        'regular' => '📚 Reguler',
                        'private' => '👤 Private',
                    ])
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('price')
                    ->label('Harga per Bulan (Reguler)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0)
                    ->visible(fn (callable $get) => $get('type') === 'regular'), // ← Hanya muncul jika Reguler

                Forms\Components\TextInput::make('price_per_session')
                    ->label('Harga per Sesi (Private)')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->visible(fn (callable $get) => $get('type') === 'private') // ← Hanya muncul jika Private
                    ->helperText('Contoh: 50000 = Rp 50.000 per sesi'),

                Forms\Components\TextInput::make('duration_months')
                    ->label('Durasi (Bulan)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),

                Forms\Components\TextInput::make('sessions_count')
                    ->label('Jumlah Pertemuan')
                    ->numeric()
                    ->nullable()
                    ->helperText('Kosongkan jika unlimited per bulan'),

                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Durasi per Pertemuan (Menit)')
                    ->numeric()
                    ->default(90)
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->nullable(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'regular',
                        'warning' => 'private',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'regular' ? '📚 Reguler' : '👤 Private'
                    ),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga/Bulan')
                    ->money('IDR')
                    ->sortable()
                    ->visible(fn ($record) => $record?->type === 'regular'),

                Tables\Columns\TextColumn::make('price_per_session')
                    ->label('Harga/Sesi')
                    ->money('IDR')
                    ->sortable()
                    ->visible(fn ($record) => $record?->type === 'private'),

                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Durasi')
                    ->formatStateUsing(fn (int $state): string => "{$state} Bulan"),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Pertemuan')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} Sesi" : 'Unlimited'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['regular' => 'Reguler', 'private' => 'Private']),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
