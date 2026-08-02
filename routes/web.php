<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => 'MESOB backend is running');

// Fallback file server for the "public" disk.
//
// php artisan storage:link creates public/storage -> storage/app/public,
// but creating symlinks on Windows requires an elevated/Administrator
// shell (or Developer Mode enabled), so it silently fails on a lot of
// XAMPP/WAMP setups and every uploaded file 404s. This route serves the
// same files directly through Laravel so uploads work even when the
// symlink is missing.
Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*');
