<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\D365VoucherTransactionImportController;

Route::get('/', function () {
    //return view('welcome');
    return redirect('/app');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/download-assignment/{file}', function ($file) {
    $filePath = storage_path("app/public/reports/{$file}");

    // Check if the file exists before downloading
    if (!file_exists($filePath)) {
        abort(404, 'File not found');
    }

    // Return a download response
    return response()->download($filePath)->deleteFileAfterSend(false);
})->name('download.assignment');

Route::get('/private-files/{path}', function ($path) {
    // Add your authorization logic here
    // Example: Only allow authenticated users
    if (!auth()->check()) {
        abort(403);
    }

    $path = decrypt($path); // Decrypt the file path if encrypted
    if (!Storage::disk('private')->exists($path)) {
        abort(404);
    }

    return Storage::disk('private')->download($path);
})->where('path', '.*')->name('private.files');

Route::get('/debug-permissions', function() {
    $user = auth()->user();
    
    // Dump current user's permissions
    dump('Direct Permissions:', $user->getDirectPermissions());
    dump('Permissions via Roles:', $user->getPermissionsViaRoles());
    dump('All Permissions:', $user->getAllPermissions());
    
    // Check specific permissions
    dump('Can ViewAny Users:', $user->can('view_any_users'));
    dump('Can Create Users:', $user->can('create_users'));
    
    // Check roles
    dump('Roles:', $user->roles()->pluck('name'));
});

Route::get('/debug-auth', function () {
    dd([
        'is_authenticated' => auth()->check(),
        'current_user' => auth()->user(),
        'auth_guard' => auth()->getDefaultDriver(),
        'session_status' => session()->all(),
    ]);
});

Route::get('/debug-session', function () {
    dd([
        'session_id' => session()->getId(),
        'session_all' => session()->all(),
        'cookies' => request()->cookies->all(),
        'headers' => request()->headers->all(),
        'server' => request()->server->all(),
    ]);
});

Route::get('/debug-login', function () {
    // Attempt to login a test user
    $user = \App\Models\User::first();
    auth()->login($user);
    
    return response()->json([
        'success' => auth()->check(),
        'user' => auth()->user(),
        'session_id' => session()->getId(),
    ]);
});

Route::get('/debug-auth-status', function () {
    // Test direct auth check
    $isAuthenticated = auth()->check();
    
    // Test session data
    $sessionUser = session('auth.user');
    
    // Get current guard
    $guard = config('filament.auth.guard');
    
    // Check if user can be retrieved
    $user = auth()->user();
    
    return response()->json([
        'is_authenticated' => $isAuthenticated,
        'session_user' => $sessionUser,
        'current_guard' => $guard,
        'user' => $user,
        'session_id' => session()->getId(),
        'all_session_data' => session()->all(),
    ]);
});

Route::get('/test-auth', function () {
    $user = \App\Models\User::first();
    
    auth()->login($user);
    
    return response()->json([
        'login_status' => auth()->check(),
        'user' => auth()->user(),
        'session_data' => session()->all(),
        'auth_guard' => auth()->getDefaultDriver(),
        'session_id' => session()->getId()
    ]);
});

Route::get('/debug-shield', function() {
    $user = auth()->user();
    
    if (!$user) {
        return response()->json([
            'error' => 'No authenticated user'
        ]);
    }

    $shieldConfig = config('filament-shield');
    $permissions = $user->getAllPermissions()->pluck('name')->toArray();
    $roles = $user->roles->pluck('name')->toArray();

    return response()->json([
        'user_id' => $user->id,
        'shield_config' => $shieldConfig,
        'user_permissions' => $permissions,
        'user_roles' => $roles,
        'is_super_admin' => $user->hasRole($shieldConfig['super_admin']['role_name'] ?? 'super_admin')
    ]);
});
require __DIR__.'/auth.php';

/* Route::post('/import-voucher-transactions', [D365VoucherTransactionImportController::class, 'import'])
    ->name('import.voucher-transactions'); */