<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberAssignmentController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('public.report'));

Route::get('/lapor', [PublicReportController::class, 'create'])->name('public.report');
Route::post('/lapor', [PublicReportController::class, 'store'])->name('public.report.store');
Route::get('/lacak/{trackingCode}', [PublicTrackingController::class, 'show'])->name('public.tracking');
Route::get('/api/public/tracking/{trackingCode}', [PublicTrackingController::class, 'data'])->name('public.tracking.data');
Route::get('/api/public/tracking/{trackingCode}/trail', [PublicTrackingController::class, 'trail'])->name('public.tracking.trail');
Route::get('/api/route', RouteController::class)->name('route.api');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/map-data', [AdminDashboardController::class, 'mapData'])->name('map-data');
    Route::get('/api/assignments/{assignment}/trail', [AdminReportController::class, 'trail'])->name('assignments.trail');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/assign-member', [AdminReportController::class, 'assignMember'])->name('reports.assign-member');
    Route::post('/reports/{report}/cancel', [AdminReportController::class, 'cancel'])->name('reports.cancel');
});

Route::middleware('auth')->prefix('member')->name('member.')->group(function (): void {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::post('/heartbeat', [MemberDashboardController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/location/update', [MemberDashboardController::class, 'updateLocation'])->name('location.update');
    Route::get('/api/active-assignment', [MemberDashboardController::class, 'activeAssignmentData'])->name('active-assignment');
    Route::get('/api/assignments/{assignment}/trail', [MemberAssignmentController::class, 'trail'])->name('assignments.trail');
    Route::get('/assignments/{assignment}', [MemberAssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/accept', [MemberAssignmentController::class, 'accept'])->name('assignments.accept');
    Route::post('/assignments/{assignment}/start', [MemberAssignmentController::class, 'start'])->name('assignments.start');
    Route::post('/assignments/{assignment}/arrive', [MemberAssignmentController::class, 'arrive'])->name('assignments.arrive');
    Route::post('/assignments/{assignment}/handling', [MemberAssignmentController::class, 'handling'])->name('assignments.handling');
    Route::post('/assignments/{assignment}/complete', [MemberAssignmentController::class, 'complete'])->name('assignments.complete');
});
