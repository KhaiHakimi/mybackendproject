<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FerryController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'ports' => \App\Models\Port::all(['id', 'name', 'latitude', 'longitude'])
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Customer / Public Authenticated Routes
    Route::get('/schedules', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
    
    // Weather Routes (Accessible by all for safety)
    Route::get('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'show'])->name('weather.show');
    
    // PUBLIC ACCESS FOR DEMO PURPOSES (Normally Admin Only)
    // Moving these out of the admin middleware so you can test simulations.
    Route::post('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'store'])->name('weather.store');
    Route::post('/ports/{port}/weather/refresh', [\App\Http\Controllers\WeatherController::class, 'refresh'])->name('weather.refresh');
    
    // Admin Only Routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('ferries', FerryController::class);
        // Schedules: Admin can create/edit/delete. Index is public.
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class)->except(['index', 'show']);
    });
});