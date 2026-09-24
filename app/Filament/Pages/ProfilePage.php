<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Profil Admin';

    protected static ?string $title = 'Profil Admin';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.profile-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Akun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->rules([
                                'unique:users,email,'.auth()->id(),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Ubah Password')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->confirmed()
                            ->rules([
                                'nullable',
                                Password::default(),
                            ])
                            ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->nullable(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $user = auth()->user();

        $user->update([
            'name' => $state['name'],
            'email' => $state['email'],
        ]);

        if (filled($state['password'])) {
            $user->update([
                'password' => Hash::make($state['password']),
            ]);
        }

        Notification::make()
            ->title('Profil berhasil diperbarui.')
            ->success()
            ->send();
    }
}
