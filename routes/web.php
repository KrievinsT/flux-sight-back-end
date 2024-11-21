<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('test-session', function (Illuminate\Http\Request $request) {
    $request->session()->put('test_key', 'test_value');
    return response()->json(['message' => 'Session set']);
});

Route::get('get-session', function (Illuminate\Http\Request $request) {
    $value = $request->session()->get('test_key');
    return response()->json(['message' => 'Session value: ' . $value]);
});


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
