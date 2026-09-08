<?php

use App\Exports\StudentsImportTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Langsung ke /admin/login jika belum login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    }

    return redirect('/admin/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/students/download-template', function () {
        return Excel::download(
            new StudentsImportTemplate,
            'template-import-siswa-zigmath.xlsx'
        );
    })->name('students.download-template');
});
