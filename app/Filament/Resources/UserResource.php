<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Manajemen User';

    protected static ?string $navigationLabel = 'User';

    protected static ?int $navigationSort = 1;

    // ==================== AUTHORIZATION ====================
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create users') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        // Proteksi: Hanya Super Admin yang boleh mengedit akun Super Admin lain
        if ($record->hasRole('super-admin') && ! auth()->user()?->hasRole('super-admin')) {
            return false;
        }

        return auth()->user()?->can('edit users') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        // Proteksi 1: Tidak boleh menghapus diri sendiri
        if ($record->id === auth()->id()) {
            return false;
        }

        // Proteksi 2: Tidak boleh menghapus Super Admin (kecuali Anda Super Admin)
        if ($record->hasRole('super-admin') && ! auth()->user()?->hasRole('super-admin')) {
            return false;
        }

        return auth()->user()?->can('delete users') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete users') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->options(function () {
                                $allRoles = Role::pluck('name', 'id');

                                // Jika yang login BUKAN Super Admin, sembunyikan opsi 'super-admin'
                                if (! auth()->user()?->hasRole('super-admin')) {
                                    return $allRoles->filter(fn ($name) => $name !== 'super-admin');
                                }

                                return $allRoles;
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $currentUserId = auth()->id();
                            $isSuperAdmin = auth()->user()?->hasRole('super-admin');

                            foreach ($records as $record) {
                                // Proteksi: Skip jika mencoba hapus diri sendiri
                                if ($record->id === $currentUserId) {
                                    Notification::make()
                                        ->title('⚠️ Aksi Ditolak')
                                        ->body('Anda tidak dapat menghapus akun Anda sendiri.')
                                        ->warning()
                                        ->send();

                                    // Batalkan seluruh operasi bulk delete
                                    throw Halt::make();
                                }

                                // Proteksi: Skip jika non-super-admin mencoba hapus super-admin
                                if ($record->hasRole('super-admin') && ! $isSuperAdmin) {
                                    Notification::make()
                                        ->title('⚠️ Aksi Ditolak')
                                        ->body("Akun '{$record->name}' adalah Super Admin dan tidak dapat dihapus.")
                                        ->danger()
                                        ->send();

                                    throw Halt::make();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
