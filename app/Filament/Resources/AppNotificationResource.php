<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppNotificationResource\Pages;
use App\Models\AppNotification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AppNotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Notifikasi';

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AppNotification::unread()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                AppNotification::query()->orderByDesc('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'payment_due' => 'Jatuh Tempo',
                        'overdue' => 'Menunggak',
                        'schedule_today' => 'Jadwal Hari Ini',
                        'incomplete_data' => 'Data Belum Lengkap',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'payment_due' => 'warning',
                        'overdue' => 'danger',
                        'schedule_today' => 'info',
                        'incomplete_data' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Dibaca')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Status Baca'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'payment_due' => 'Jatuh Tempo',
                        'overdue' => 'Menunggak',
                        'schedule_today' => 'Jadwal Hari Ini',
                        'incomplete_data' => 'Data Belum Lengkap',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('markRead')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (AppNotification $record): bool => ! $record->is_read)
                    ->action(function (AppNotification $record): void {
                        $record->update([
                            'is_read' => true,
                        ]);
                    }),

                Tables\Actions\Action::make('openLink')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn (AppNotification $record): bool => filled($record->link))
                    ->url(fn (AppNotification $record): string => $record->link),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markAllRead')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Collection $records): void {
                        $records->each(function (AppNotification $record) {
                            $record->update(['is_read' => true]);
                        });
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppNotifications::route('/'),
        ];
    }
}
