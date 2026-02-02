<?php

use App\Http\Controllers\FerryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/logs', [\App\Http\Controllers\DashboardController::class, 'systemLogs'])->middleware(['auth', 'verified', 'admin'])->name('dashboard.logs');
Route::post('/dashboard/geo-analysis', [\App\Http\Controllers\DashboardController::class, 'geoAnalysis'])->middleware(['auth', 'verified'])->name('dashboard.geo_analysis');
Route::post('/dashboard/analyze-route', [\App\Http\Controllers\DashboardController::class, 'analyzeRoute'])->middleware(['auth', 'verified'])->name('dashboard.analyze_route');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Customer / Public Routes (Accessible by guests)
Route::get('/schedules', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
Route::get('/our-fleet', [\App\Http\Controllers\FerryController::class, 'publicIndex'])->name('ferries.public_index');
Route::get('/our-fleet/{ferry}', [\App\Http\Controllers\FerryController::class, 'publicShow'])->name('ferries.public_show');

// Weather Routes (Accessible by all for safety)
Route::get('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'show'])->name('weather.show');

// PUBLIC ACCESS FOR DEMO PURPOSES - MOVED TO ADMIN
// Route::post('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'store'])->name('weather.store');
Route::post('/ports/{port}/weather/refresh', [\App\Http\Controllers\WeatherController::class, 'refresh'])->name('weather.refresh');
Route::post('/weather/refresh-all', [\App\Http\Controllers\WeatherController::class, 'refreshAll'])->name('weather.refresh_all');
Route::get('/weather/wind-data', [\App\Http\Controllers\WeatherController::class, 'windData'])->name('weather.wind_data');
Route::get('/weather/wave-data', [\App\Http\Controllers\WeatherController::class, 'waveData'])->name('weather.wave_data');

Route::middleware(['auth', 'verified'])->group(function () {
    // Authenticated actions
    Route::post('/ferries/{ferry}/reviews', [\App\Http\Controllers\FerryController::class, 'storeReview'])->name('ferries.reviews.store');

    // Admin Only Routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('ferries', FerryController::class);
        // Schedules: Admin can create/edit/delete. Index is public.
        Route::post('/schedules/generate', [\App\Http\Controllers\ScheduleController::class, 'generateDaily'])->name('schedules.generate');
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class)->except(['index', 'show']);

        // Weather Simulation & Data Fetching
        Route::post('/ports/{port}/weather', [\App\Http\Controllers\WeatherController::class, 'store'])->name('weather.store');
    });
});
