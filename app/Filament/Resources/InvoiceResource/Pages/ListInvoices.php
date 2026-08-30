<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('generatePrivateBulk')
                ->label('🎯 Generate Invoice Private (Massal)')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->form([
                    TextInput::make('period')
                        ->label('Periode Bulan (YYYY-MM)')
                        ->default(now()->format('Y-m'))
                        ->required(),

                    Repeater::make('items')
                        ->label('Daftar Siswa Private Aktif')
                        ->schema([
                            TextInput::make('student_id')->hidden(),

                            TextInput::make('student_name')
                                ->label('Nama Siswa')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('sessions')
                                ->label('Jumlah Sesi')
                                ->numeric()
                                ->required()
                                ->minValue(0),

                            TextInput::make('price_per_session')
                                ->label('Harga/Sesi')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->columnSpanFull()
                        ->helperText('Edit "Jumlah Sesi" jika sesi aktual siswa berbeda dari default paket.'),
                ])
                ->fillForm(function (): array {
                    $students = Student::where('status', 'active')
                        ->whereHas('package', fn ($q) => $q->where('type', 'private'))
                        ->with('package')
                        ->get();

                    return [
                        'period' => now()->format('Y-m'),
                        'items' => $students->map(fn (Student $s) => [
                            'student_id' => $s->id,
                            'student_name' => $s->name,
                            'sessions' => $s->package->sessions_count ?? 8,
                            'price_per_session' => 'Rp '.number_format((float) ($s->package->price_per_session ?? 0), 0, ',', '.'),
                        ])->toArray(),
                    ];
                })
                ->action(function (array $data): void {
                    $period = $data['period'];
                    $created = 0;
                    $skipped = 0;

                    foreach ($data['items'] ?? [] as $item) {
                        $student = Student::with('package')->find($item['student_id'] ?? null);

                        if (! $student || ! $student->package) {
                            $skipped++;

                            continue;
                        }

                        // Skip jika invoice periode ini sudah ada
                        $exists = Invoice::where('student_id', $student->id)
                            ->where('period', $period)
                            ->exists();

                        if ($exists) {
                            $skipped++;

                            continue;
                        }

                        $sessions = (int) ($item['sessions'] ?? 0);
                        $pricePerSession = (float) ($student->package->price_per_session ?? 0);
                        $total = $sessions * $pricePerSession;

                        if ($sessions <= 0 || $total <= 0) {
                            $skipped++;

                            continue;
                        }

                        $dueDay = $student->due_day ?? 5;
                        $dueDate = Carbon::parse("{$period}-01")->day(min($dueDay, 28));

                        $seq = Invoice::where('period', $period)->withTrashed()->count() + 1;
                        $invoiceNo = 'INV/ZGM/'.str_replace('-', '', $period).'/'.str_pad($seq, 4, '0', STR_PAD_LEFT);

                        Invoice::create([
                            'invoice_no' => $invoiceNo,
                            'student_id' => $student->id,
                            'period' => $period,
                            'due_date' => $dueDate,
                            'amount' => $total,
                            'discount' => 0,
                            'paid_amount' => 0,
                            'remaining_balance' => $total,
                            'status' => 'unpaid',
                            'notes' => "Private: {$sessions} sesi × Rp ".number_format($pricePerSession, 0, ',', '.'),
                        ]);

                        $created++;
                    }

                    Notification::make()
                        ->title('Generate Invoice Private Selesai')
                        ->body("✅ {$created} invoice dibuat • ⏭️ {$skipped} dilewati (sudah ada / 0 sesi).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
