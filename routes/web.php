<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Application Routes (Private to logged-in users only)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');

    // Default entry: Calendar
    Route::get('/', [MatchController::class, 'calendar'])->name('calendar');
    Route::get('/calendar', [MatchController::class, 'calendar']);
    Route::get('/calendar/data', [MatchController::class, 'calendarData'])->name('calendar.data');

    // Internal Dashboard & Matches
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show')->withTrashed();
    Route::post('/matches', [MatchController::class, 'store'])->name('matches.store');
    Route::post('/matches/{match}/confirm', [MatchController::class, 'confirm'])->name('matches.confirm');
    Route::post('/matches/{match}/dispute', [MatchController::class, 'dispute'])->name('matches.dispute');
    Route::post('/matches/{match}/pay-prize', [MatchController::class, 'payPrize'])->name('matches.pay-prize');
    Route::post('/matches/{match}/confirm-prize', [MatchController::class, 'confirmPrize'])->name('matches.confirm-prize');
    Route::post('/matches/{match}/reject-prize', [MatchController::class, 'rejectPrize'])->name('matches.reject-prize');

    // Leaderboard & Members
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{username}', [MemberController::class, 'show'])->name('members.show');
    Route::post('/members/{user}/telegram-code', [MemberController::class, 'generateTelegramCode'])->name('members.telegram-code');

    // Duel Challenges (Pure AJAX)
    Route::post('/challenges', [ChallengeController::class, 'store'])->name('challenges.store');
    Route::get('/challenges/check', [ChallengeController::class, 'checkPending'])->name('challenges.check');
    Route::post('/challenges/{challenge}/accept', [ChallengeController::class, 'accept'])->name('challenges.accept');
    Route::post('/challenges/{challenge}/decline', [ChallengeController::class, 'decline'])->name('challenges.decline');
    Route::post('/challenges/{challenge}/resolve', [ChallengeController::class, 'resolve'])->name('challenges.resolve');

    // Realtime Notifications (Pure AJAX)
    Route::get('/notifications', [ChallengeController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/mark-read', [ChallengeController::class, 'markNotificationsRead'])->name('notifications.mark-read');

    // Admin Routes (Admin role only)
    Route::middleware(EnsureUserIsAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::post('/matches/{match}/resolve', [AdminController::class, 'resolveDispute'])->name('matches.resolve');
        Route::post('/matches/{match}/cancel', [AdminController::class, 'cancelMatch'])->name('matches.cancel');
        Route::post('/matches/{match}/toggle-hidden', [AdminController::class, 'toggleMatchHidden'])->name('matches.toggle-hidden');
        Route::post('/matches/{match}/trash', [AdminController::class, 'trashMatch'])->name('matches.trash');
        Route::delete('/matches/{match}', [AdminController::class, 'trashMatch'])->name('matches.destroy');
        Route::post('/trash/{id}/restore', [AdminController::class, 'restoreMatch'])->name('trash.restore');
        Route::post('/trash/{id}/force-delete', [AdminController::class, 'forceDeleteMatch'])->name('trash.force-delete');
        Route::delete('/trash/{id}/force-delete', [AdminController::class, 'forceDeleteMatch']);
        Route::post('/trash/empty', [AdminController::class, 'emptyTrash'])->name('trash.empty');
        Route::delete('/trash/empty', [AdminController::class, 'emptyTrash']);
        Route::post('/users/{user}/status', [AdminController::class, 'toggleUserStatus'])->name('users.status');
        Route::post('/users/{user}/role', [AdminController::class, 'toggleUserRole'])->name('users.role');
        Route::post('/users/{user}/password', [AdminController::class, 'resetUserPassword'])->name('users.password');
        Route::post('/bot/config', [AdminController::class, 'updateBotConfig'])->name('bot.config');
        Route::post('/telegram/daily-report', [AdminController::class, 'sendDailyReport'])->name('telegram.daily-report');
        Route::post('/telegram/invite-all', [AdminController::class, 'inviteGroupMembers'])->name('telegram.invite-all');
        Route::post('/logo', [AdminController::class, 'updateLogo'])->name('logo.update');
        Route::post('/logo/remove', [AdminController::class, 'removeLogo'])->name('logo.remove');
    });
});

// Telegram Bot Webhook endpoint (External bot access)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// Secure WiFi verification endpoint
Route::post('/wifi/verify', function (Request $request) {
    $clientIp = trim((string) $request->input('ip', ''));
    $allowedIp = trim((string) config('app.allowed_wifi_ip', '222.252.18.26'));

    $isAllowed = ($clientIp !== '' && hash_equals($allowedIp, $clientIp));

    return response()->json([
        'allowed' => $isAllowed,
    ]);
})->name('wifi.verify');
