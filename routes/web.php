<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Application Routes (Private to logged-in users only)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Default entry: Calendar
    Route::get('/', [MatchController::class, 'calendar'])->name('calendar');
    Route::get('/calendar', [MatchController::class, 'calendar']);
    Route::get('/calendar/data', [MatchController::class, 'calendarData'])->name('calendar.data');

    // Internal Dashboard & Matches
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');
    Route::post('/matches', [MatchController::class, 'store'])->name('matches.store');
    Route::post('/matches/{match}/confirm', [MatchController::class, 'confirm'])->name('matches.confirm');
    Route::post('/matches/{match}/dispute', [MatchController::class, 'dispute'])->name('matches.dispute');

    // Leaderboard & Members
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{username}', [MemberController::class, 'show'])->name('members.show');
    Route::post('/members/{user}/telegram-code', [MemberController::class, 'generateTelegramCode'])->name('members.telegram-code');

    // Admin Routes (Admin role only)
    Route::middleware(EnsureUserIsAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::post('/matches/{match}/resolve', [AdminController::class, 'resolveDispute'])->name('matches.resolve');
        Route::post('/users/{user}/status', [AdminController::class, 'toggleUserStatus'])->name('users.status');
        Route::post('/users/{user}/role', [AdminController::class, 'toggleUserRole'])->name('users.role');
        Route::post('/bot/config', [AdminController::class, 'updateBotConfig'])->name('bot.config');
        Route::post('/logo', [AdminController::class, 'updateLogo'])->name('logo.update');
        Route::post('/logo/remove', [AdminController::class, 'removeLogo'])->name('logo.remove');
    });
});

// Telegram Bot Webhook endpoint (External bot access)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
