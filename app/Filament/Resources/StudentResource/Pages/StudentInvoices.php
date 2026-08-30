<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StudentInvoices extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $resource = StudentResource::class;

    protected static string $view = 'filament.resources.student-resource.pages.student-invoices';

    public ?string $student_id = null;

    public function mount(int|string $record): void
    {
        $this->student_id = $record;
    }

    public function getRecord(): \App\Models\Student
    {
        return \App\Models\Student::findOrFail($this->student_id);
    }

    public function getTitle(): string
    {
        return 'Tagihan & Angsuran: ' . $this->getRecord()->name;
    }

    public static function getNavigationLabel(): string
    {
        return 'Tagihan Siswa';
    }

    public function table(Table $table): Table
    {
        $student = $this->getRecord();

        return $table
            ->query(
                Invoice::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('period')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn(string $state): string =>
                        \Carbon\Carbon::parse($state . '-01')->translatedFormat('F Y')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn(Invoice $record): string =>
                        $record->isOverdue() ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tagihan')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa')
                    ->money('IDR')
                    ->color(fn(Invoice $record): string =>
                        $record->remaining_balance > 0 ? 'danger' : 'success'
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'draft',
                        'danger' => 'unpaid',
                        'warning' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'draft' => 'Draft',
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('💰 Input Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(Invoice $record) => in_array($record->status, ['unpaid', 'partial', 'overdue']))
                    ->form([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Tagihan')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn(Invoice $record) => 'Rp ' . number_format($record->amount, 0, ',', '.')),

                        Forms\Components\TextInput::make('remaining_display')
                            ->label('Sisa Tagihan')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn(Invoice $record) => 'Rp ' . number_format($record->remaining_balance, 0, ',', '.')),

                        Forms\Components\TextInput::make('pay_amount')
                            ->label('Jumlah yang Dibayar (Rp)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn(Invoice $record) => $record->remaining_balance)
                            ->placeholder('Contoh: 200000')
                            ->helperText(fn(Invoice $record) => 'Maksimal: Rp ' . number_format($record->remaining_balance, 0, ',', '.')),

                        Forms\Components\Select::make('method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => '💵 Tunai',
                                'transfer' => '🏦 Transfer Bank',
                                'qris' => '📱 QRIS',
                                'e_wallet' => '💳 E-Wallet',
                                'other' => '📝 Lainnya',
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('No. Referensi')
                            ->placeholder('No. resi transfer (opsional)')
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan')
                            ->rows(2)
                            ->nullable(),

                        Forms\Components\FileUpload::make('proof')
                            ->label('Upload Bukti Pembayaran')
                            ->directory('payment-proofs')
                            ->image()
                            ->maxSize(5120)
                            ->nullable(),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            // Buat record pembayaran
                            Payment::create([
                                'payment_no' => 'PAY/' . now()->format('Ymd') . '/' . strtoupper(Str::random(6)),
                                'invoice_id' => $record->id,
                                'amount' => $data['pay_amount'],
                                'method' => $data['method'],
                                'reference_number' => $data['reference_number'] ?? null,
                                'proof_path' => $data['proof'] ?? null,
                                'notes' => $data['notes'] ?? null,
                                'verified_by' => auth()->id(),
                                'paid_at' => now(),
                            ]);

                            // Update invoice
                            $newPaid = $record->paid_amount + $data['pay_amount'];
                            $newRemaining = $record->remaining_balance - $data['pay_amount'];

                            $record->update([
                                'paid_amount' => $newPaid,
                                'remaining_balance' => max(0, $newRemaining),
                                'status' => $newRemaining <= 0 ? 'paid' : 'partial',
                            ]);
                        });

                        Notification::make()
                            ->title('Pembayaran Berhasil Dicatat!')
                            ->body('Rp ' . number_format($data['pay_amount'], 0, ',', '.') . ' telah diterima.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('history')
                    ->label('📜 Riwayat')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Riwayat Pembayaran')
                    ->modalContent(function (Invoice $record) {
                        $payments = $record->payments()->orderByDesc('paid_at')->get();
                        return view('filament.components.payment-history', compact('payments'));
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->emptyStateHeading('Belum ada tagihan')
            ->emptyStateDescription('Tagihan akan digenerate otomatis setiap bulan.');
    }
}
