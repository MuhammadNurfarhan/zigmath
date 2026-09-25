<?php

namespace App\Filament\Pages;

use App\Exports\ArrearsReportExport;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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
                    ->formatStateUsing(fn (?float $state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.')),

                TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->formatStateUsing(fn (?float $state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.'))
                    ->color('success'),

                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->formatStateUsing(fn (?float $state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.'))
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
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        $fileName = 'laporan-tunggakan-'.now()->format('Y-m-d').'.xlsx';
                        Excel::store(new ArrearsReportExport($this->getFilteredTableQuery()), 'temp/'.$fileName, 'public');

                        Notification::make()->title('Excel Berhasil Digenerate!')->success()
                            ->actions([NotificationAction::make('download')->label('Download Excel')->url(asset('storage/temp/'.$fileName), shouldOpenInNewTab: true)->button()->color('success')])
                            ->send();
                    }),

                Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function () {
                        $invoices = $this->getFilteredTableQuery()->get();
                        $pdf = Pdf::loadView('pdf.arrears-report', ['invoices' => $invoices]);
                        $fileName = 'laporan-tunggakan-'.now()->format('Y-m-d').'.pdf';
                        Storage::disk('public')->put('temp/'.$fileName, $pdf->output());

                        Notification::make()->title('PDF Berhasil Digenerate!')->success()
                            ->actions([NotificationAction::make('download')->label('Download PDF')->url(asset('storage/temp/'.$fileName), shouldOpenInNewTab: true)->button()->color('danger')])
                            ->send();
                    }),
            ]);
    }
}
