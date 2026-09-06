<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportArrearsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Tunggakan';

    protected static ?string $title = 'Laporan Tunggakan';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.report-arrears';

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
                Invoice::query()
                    ->with(['student.package'])
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->orderBy('due_date')
            )
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('No. Invoice')
                    ->searchable(),

                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable(),

                TextColumn::make('student.package.name')
                    ->label('Paket'),

                TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state.'-01')->translatedFormat('F Y')
                    ),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color(fn (Invoice $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                TextColumn::make('amount')
                    ->label('Tagihan')
                    ->money('IDR'),

                TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->color('success'),

                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->color('danger'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'overdue' => 'Terlambat',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Cicilan',
                        'overdue' => 'Terlambat',
                    ]),

                SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
