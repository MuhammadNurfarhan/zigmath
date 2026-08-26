<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Riwayat Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'payment_no';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Pembayaran')->schema([
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->relationship('invoice', 'invoice_no') // Menampilkan invoice_no
                    ->searchable()
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->invoice_no . ' - ' . $record->student->name)
                    ->disabledOn('edit'),

                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0),

                Forms\Components\Select::make('method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => '💵 Tunai',
                        'transfer' => '🏦 Transfer Bank',
                        'qris' => '📱 QRIS',
                        'e_wallet' => '💳 E-Wallet',
                        'other' => '📝 Lainnya',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('reference_number')
                    ->label('No. Referensi / Resi')
                    ->nullable(),

                Forms\Components\FileUpload::make('proof_path')
                    ->label('Bukti Pembayaran')
                    ->directory('payment-proofs')
                    ->image()
                    ->maxSize(5120) // 5MB
                    ->nullable()
                    ->openable(),

                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Tanggal & Waktu Bayar')
                    ->default(now())
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_no')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('invoice.invoice_no')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'cash' => 'green',
                        'transfer' => 'blue',
                        'qris' => 'purple',
                        'e_wallet' => 'indigo',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'cash' => '💵 Tunai',
                        'transfer' => '🏦 Transfer',
                        'qris' => '📱 QRIS',
                        'e_wallet' => '💳 E-Wallet',
                        default => '📝 Lainnya',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('verifier.name')
                    ->label('Diverifikasi Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('proof_path')
                    ->label('Bukti')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn(Payment $record) => $record->proof_path ? 'Ada bukti upload' : 'Tanpa bukti'),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'e_wallet' => 'E-Wallet',
                        'other' => 'Lainnya',
                    ]),
                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from')->label('Tanggal Dari'),
                        Forms\Components\DatePicker::make('paid_until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['paid_from'], fn($q, $date) => $q->whereDate('paid_at', '>=', $date))
                            ->when($data['paid_until'], fn($q, $date) => $q->whereDate('paid_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Tambahkan data tambahan untuk view jika perlu
                        return $data;
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Riwayat Pembayaran?')
                    ->modalDescription('Data pembayaran akan dihapus (soft delete). Pastikan ini bukan kesalahan fatal karena mempengaruhi saldo invoice.'),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
