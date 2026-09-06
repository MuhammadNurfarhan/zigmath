<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportIncomePage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Pemasukan';

    protected static ?string $title = 'Laporan Pemasukan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.report-income';

    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['invoice.student'])
                    ->orderByDesc('paid_at')
            )
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('payment_no')
                    ->label('No. Pembayaran')
                    ->searchable(),

                TextColumn::make('invoice.student.name')
                    ->label('Siswa')
                    ->searchable(),

                TextColumn::make('invoice.invoice_no')
                    ->label('No. Invoice')
                    ->searchable(),

                TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'e_wallet' => 'E-Wallet',
                        default => 'Lainnya',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'warning',
                        'e_wallet' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),

                        DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn ($q) => $q->whereDate('paid_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn ($q) => $q->whereDate('paid_at', '<=', $data['to'])
                            );
                    }),

                SelectFilter::make('method')
                    ->label('Metode')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'e_wallet' => 'E-Wallet',
                        'other' => 'Lainnya',
                    ]),
            ])
            ->defaultSort('paid_at', 'desc');
    }
}
