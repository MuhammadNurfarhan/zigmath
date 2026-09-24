<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BackupPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Backup Database';

    protected static ?string $title = 'Backup Database';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.backup-page';

    public function backup(): void
    {
        Artisan::call('zigmath:backup');

        Notification::make()
            ->title('Backup database selesai.')
            ->success()
            ->send();
    }

    public function getBackups(): array
    {
        $directory = storage_path('app/backups');

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(function ($file) {
                return [
                    'name' => $file->getFilename(),
                    'size' => number_format($file->getSize() / 1024, 2).' KB',
                    'time' => date('d M Y H:i', $file->getMTime()),
                ];
            })
            ->sortByDesc('time')
            ->values()
            ->toArray();
    }
}
