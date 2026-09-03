<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Tagihan / Invoice';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'invoice_no';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Tagihan')->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    // ->disabledOn('edit')
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (! $state) {
                            return;
                        }

                        $student = Student::with('package')->find($state);
                        if ($student && $student->package) {
                            // Logika Private Per Sesi
                            if ($student->class_type === 'private' && $student->package->price_per_session > 0) {
                                $totalAmount = $student->package->price_per_session * ($student->package->sessions_count ?? 1);
                                $set('amount', $totalAmount);
                                $set('notes', 'Private: '.($student->package->sessions_count ?? 1).' sesi x Rp '.number_format($student->package->price_per_session, 0, ',', '.'));
                            }
                            // Logika Reguler / Private Paket Tetap
                            else {
                                $set('amount', $student->package->price);
                                $set('notes', $student->class_type === 'private' ? 'Paket Private' : 'Paket Reguler');
                            }
                        }
                    }),

                Forms\Components\TextInput::make('period')
                    ->label('Periode (YYYY-MM)')
                    ->placeholder('2024-06')
                    ->required()
                    ->maxLength(7)
                    ->helperText('Format: YYYY-MM (Contoh: 2024-06)')
                    ->disabledOn('edit'),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Tanggal Jatuh Tempo')
                    ->required()
                    ->native(false),

                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah Tagihan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    // ->minValue(0)
                    ->readOnly()
                    ->helperText('Otomatis terisi berdasarkan paket siswa.'),

                Forms\Components\TextInput::make('discount')
                    ->label('Diskon')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->minValue(0),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->default('unpaid')
                    ->required(),

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
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state.'-01')->translatedFormat('F Y')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (Invoice $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa')
                    ->money('IDR')
                    ->color(fn (Invoice $record): string => $record->remaining_balance > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    }),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ]),
                Tables\Filters\SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')->label('Jatuh Tempo Dari'),
                        Forms\Components\DatePicker::make('due_until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['due_from'], fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
                            ->when($data['due_until'], fn ($q, $date) => $q->whereDate('due_date', '<=', $date));
                    }),
            ])
            ->actions([
                // ACTION INPUT BAYAR LANGSUNG DARI TABEL
                Action::make('pay')
                    ->label('💰 Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record) => in_array($record->status, ['unpaid', 'partial', 'overdue']))
                    ->form([
                        Forms\Components\TextInput::make('remaining_display')
                            ->label('Sisa Tagihan')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn (Invoice $record) => 'Rp '.number_format($record->remaining_balance, 0, ',', '.')),

                        Forms\Components\TextInput::make('pay_amount')
                            ->label('Jumlah Dibayar (Rp)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn (Invoice $record) => $record->remaining_balance)
                            ->placeholder('Contoh: 200000'),

                        Forms\Components\Select::make('method')
                            ->label('Metode')
                            ->options([
                                'cash' => '💵 Tunai',
                                'transfer' => '🏦 Transfer',
                                'qris' => '📱 QRIS',
                                'e_wallet' => '💳 E-Wallet',
                                'other' => '📝 Lainnya',
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('No. Referensi')
                            ->nullable(),

                        Forms\Components\FileUpload::make('proof')
                            ->label('Bukti')
                            ->directory('payment-proofs')
                            ->image()
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            Payment::create([
                                'payment_no' => 'PAY/'.now()->format('Ymd').'/'.strtoupper(Str::random(5)),
                                'invoice_id' => $record->id,
                                'amount' => $data['pay_amount'],
                                'method' => $data['method'],
                                'reference_number' => $data['reference_number'] ?? null,
                                'proof_path' => $data['proof'] ?? null,
                                'notes' => $data['notes'] ?? null,
                                'verified_by' => Auth::id(),
                                'paid_at' => now(),
                            ]);

                            $newPaid = $record->paid_amount + $data['pay_amount'];
                            $newRemaining = max(0, $record->remaining_balance - $data['pay_amount']);

                            $record->update([
                                'paid_amount' => $newPaid,
                                'remaining_balance' => $newRemaining,
                                'status' => $newRemaining <= 0 ? 'paid' : 'partial',
                            ]);
                        });

                        Notification::make()
                            ->title('Pembayaran Berhasil!')
                            ->body('Rp '.number_format($data['pay_amount'], 0, ',', '.').' tercatat.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Invoice $record) => $record->status === 'draft'),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
