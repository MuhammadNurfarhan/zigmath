<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()
            ->mapWithKeys(function (Setting $setting) {
                return [
                    $setting->key => Setting::get($setting->key),
                ];
            })
            ->toArray();

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identitas Bimbel')
                    ->schema([
                        TextInput::make('bimbel_name')
                            ->label('Nama Bimbel')
                            ->default('Zigmath')
                            ->required(),

                        FileUpload::make('bimbel_logo')
                            ->label('Logo Bimbel')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->maxSize(2048),

                        Textarea::make('bimbel_address')
                            ->label('Alamat')
                            ->rows(2),

                        TextInput::make('bimbel_phone')
                            ->label('Nomor Telepon')
                            ->tel(),

                        TextInput::make('bimbel_email')
                            ->label('Email')
                            ->email(),
                    ])
                    ->columns(2),

                Section::make('Pengaturan Invoice & Tagihan')
                    ->schema([
                        TextInput::make('invoice_prefix')
                            ->label('Prefix Invoice')
                            ->default('INV/ZGM/')
                            ->required(),

                        Select::make('late_fee_type')
                            ->label('Jenis Denda Keterlambatan')
                            ->options([
                                'none' => 'Tanpa Denda',
                                'fixed' => 'Nominal Tetap',
                                'percent' => 'Persentase',
                            ])
                            ->default('none')
                            ->required(),

                        TextInput::make('late_fee_value')
                            ->label('Nilai Denda')
                            ->numeric()
                            ->default(0)
                            ->helperText('Jika fixed: nominal rupiah. Jika percent: persen dari tagihan.'),

                        Textarea::make('invoice_footer')
                            ->label('Footer Invoice')
                            ->rows(2)
                            ->placeholder('Terima kasih telah membayar tepat waktu.'),
                    ])
                    ->columns(2),

                Section::make('Pengaturan Pembayaran')
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->placeholder('BCA'),

                        TextInput::make('bank_number')
                            ->label('Nomor Rekening'),

                        TextInput::make('bank_holder')
                            ->label('Atas Nama'),
                    ])
                    ->columns(3),

                Section::make('Sistem')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Mode Maintenance')
                            ->default(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan.')
            ->success()
            ->send();
    }
}
