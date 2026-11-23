<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Kader\AnalyticsController as KaderAnalyticsController;
use App\Http\Controllers\Kader\DashboardController as KaderDashboardController;
use App\Http\Controllers\Kader\MeasurementController;
use App\Http\Controllers\Kader\MeasurementHistoryController;
use App\Http\Controllers\Nakes\AnalyticsController as NakesAnalyticsController;
use App\Http\Controllers\Nakes\DashboardController as NakesDashboardController;
use App\Http\Controllers\Nakes\ReviewController as NakesReviewController;
use App\Http\Controllers\Nakes\RiskReviewController;
use App\Http\Controllers\Ortu\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Ortu\GrowthController as OrtuGrowthController;
use App\Http\Controllers\Ortu\ScheduleController as OrtuScheduleController;
use App\Http\Controllers\Puskesmas\DashboardController as PuskesmasDashboardController;
use App\Http\Controllers\Puskesmas\PosyanduController as PuskesmasPosyanduController;
use App\Http\Controllers\Puskesmas\ReportController;
use App\Http\Controllers\Puskesmas\SettingsController as PuskesmasSettingsController;
use App\Http\Controllers\Puskesmas\UserController as PuskesmasUserController;
use App\Http\Controllers\Shared\ChildController;
use App\Http\Controllers\Shared\FollowUpController;
use App\Http\Controllers\Shared\NutritionController;
use App\Http\Controllers\Shared\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/masuk');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', function () {
        return redirect()->route(request()->user()->homeRoute());
    })->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/anak', [ChildController::class, 'index'])->name('children.index');
    Route::get('/anak/{child}', [ChildController::class, 'show'])->name('children.show');
    Route::post('/anak/{child}/pengukuran', [MeasurementController::class, 'store'])->name('measurements.store');
    Route::post('/anak/{child}/pengukuran/pratinjau', [MeasurementController::class, 'preview'])->name('measurements.preview');

    Route::get('/tindak-lanjut', [FollowUpController::class, 'index'])->name('follow-ups.index');

    Route::middleware('role:kader,nakes,admin_puskesmas')->group(function (): void {
        Route::post('/anak/{child}/tindak-lanjut', [FollowUpController::class, 'store'])->name('follow-ups.store');
        Route::patch('/tindak-lanjut/{followUp}/selesai', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
    });

    Route::get('/edukasi-gizi', [NutritionController::class, 'index'])->name('nutrition.index');

    Route::middleware('role:kader')->prefix('kader')->name('kader.')->group(function (): void {
        Route::get('/dashboard', KaderDashboardController::class)->name('dashboard');
        Route::get('/pengukuran', MeasurementHistoryController::class)->name('measurements');
        Route::get('/analisis', KaderAnalyticsController::class)->name('analytics');
    });

    Route::middleware('role:ortu')->prefix('orang-tua')->name('ortu.')->group(function (): void {
        Route::get('/beranda', OrtuDashboardController::class)->name('dashboard');
        Route::get('/pertumbuhan', OrtuGrowthController::class)->name('growth');
        Route::get('/jadwal', OrtuScheduleController::class)->name('schedule');
    });

    Route::middleware('role:nakes')->prefix('tenaga-kesehatan')->name('nakes.')->group(function (): void {
        Route::get('/dashboard', NakesDashboardController::class)->name('dashboard');
        Route::get('/peninjauan', NakesReviewController::class)->name('reviews');
        Route::get('/analisis', NakesAnalyticsController::class)->name('analytics');
        Route::patch('/peninjauan/{risk}/selesai', [RiskReviewController::class, 'resolve'])->name('risks.resolve');
        Route::patch('/peninjauan/{risk}/tutup', [RiskReviewController::class, 'dismiss'])->name('risks.dismiss');
    });

    Route::middleware('role:admin_puskesmas')->prefix('puskesmas')->name('puskesmas.')->group(function (): void {
        Route::get('/dashboard', PuskesmasDashboardController::class)->name('dashboard');
        Route::get('/posyandu', PuskesmasPosyanduController::class)->name('posyandu');
        Route::get('/pengguna', PuskesmasUserController::class)->name('users');
        Route::get('/pengaturan', PuskesmasSettingsController::class)->name('settings');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports');
        Route::get('/laporan/unduh', [ReportController::class, 'download'])->name('reports.download');
    });
});
