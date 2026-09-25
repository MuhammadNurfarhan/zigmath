<?php

namespace App\Filament\Pages;

use App\Exports\StudentsReportExport;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
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

class ReportStudentsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static ?string $title = 'Laporan Siswa';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.report-students';

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
                Student::query()
                    ->with(['package'])
                    ->withSum('invoices', 'amount')
                    ->withSum('invoices', 'paid_amount')
                    ->withSum('invoices', 'remaining_balance')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('class_type')
                    ->label('Kelas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'regular' ? 'Reguler' : 'Private')
                    ->color(fn (string $state): string => $state === 'regular' ? 'primary' : 'warning'),

                TextColumn::make('package.name')
                    ->label('Paket'),

                TextColumn::make('parent_name')
                    ->label('Orang Tua')
                    ->searchable(),

                TextColumn::make('parent_phone')
                    ->label('No HP Orang Tua'),

                TextColumn::make('due_day')
                    ->label('Jatuh Tempo')
                    ->formatStateUsing(fn (int $state): string => 'Tanggal '.$state),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'cuti' => 'Cuti',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'cuti' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('invoices_sum_amount')
                    ->label('Total Tagihan')
                    ->money('IDR'),

                TextColumn::make('invoices_sum_paid_amount')
                    ->label('Total Terbayar')
                    ->money('IDR')
                    ->color('success'),

                TextColumn::make('invoices_sum_remaining_balance')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->color(fn (?float $state): string => ($state ?? 0) > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('class_type')
                    ->label('Kelas')
                    ->options([
                        'regular' => 'Reguler',
                        'private' => 'Private',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'cuti' => 'Cuti',
                    ]),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        $fileName = 'laporan-siswa-'.now()->format('Y-m-d').'.xlsx';
                        Excel::store(new StudentsReportExport($this->getFilteredTableQuery()), 'temp/'.$fileName, 'public');

                        Notification::make()->title('Excel Berhasil Digenerate!')->success()
                            ->actions([NotificationAction::make('download')->label('Download Excel')->url(asset('storage/temp/'.$fileName), shouldOpenInNewTab: true)->button()->color('success')])
                            ->send();
                    }),

                Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function () {
                        $students = $this->getFilteredTableQuery()->get();
                        $pdf = Pdf::loadView('pdf.students-report', ['students' => $students]);
                        $fileName = 'laporan-siswa-'.now()->format('Y-m-d').'.pdf';
                        Storage::disk('public')->put('temp/'.$fileName, $pdf->output());

                        Notification::make()->title('PDF Berhasil Digenerate!')->success()
                            ->actions([NotificationAction::make('download')->label('Download PDF')->url(asset('storage/temp/'.$fileName), shouldOpenInNewTab: true)->button()->color('danger')])
                            ->send();
                    }),
            ]);
    }
}
