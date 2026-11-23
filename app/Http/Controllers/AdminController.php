<?php

namespace App\Http\Controllers;

use App\Models\BilliardMatch;
use App\Models\MatchHistory;
use App\Models\Setting;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        protected BilliardMatchService $matchService,
        protected TelegramBotService $telegramBot
    ) {}

    public function index(): View
    {
        $this->matchService->autoConfirmExpiredMatches();

        // 1. Pending disputes needing resolution
        $disputedMatches = BilliardMatch::where('status', 'disputed')
            ->with(['creator', 'player1', 'player2', 'winner', 'loser', 'histories'])
            ->orderBy('disputed_at', 'desc')
            ->get();

        // 2. All members
        $members = User::orderBy('created_at', 'desc')->get();

        // 3. System Activity Log
        $recentHistories = MatchHistory::with(['match.player1', 'match.player2', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // 4. Telegram Config
        $botConfig = TelegramBotConfig::current();

        // 5. Logo Settings
        $currentLogoUrl = Setting::getLogoUrl();
        $hasCustomLogo = ! empty(Setting::get('app_logo'));

        return view('admin.index', compact('disputedMatches', 'members', 'recentHistories', 'botConfig', 'currentLogoUrl', 'hasCustomLogo'));
    }

    public function resolveDispute(Request $request, BilliardMatch $match): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'resolution' => ['required', 'in:confirm_initial,reverse_winner,cancel'],
            'admin_notes' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'admin_notes.required' => 'Quản trị viên bắt buộc phải nhập lý do khi đưa ra quyết định xử lý!',
        ]);

        try {
            $this->matchService->adminResolveMatch(
                $match,
                $admin,
                $validated['resolution'],
                null,
                $validated['admin_notes']
            );

            return back()->with('success', 'Tranh chấp đã được xử lý và ghi vào lịch sử thao tác!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeUser(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'in:member,admin'],
            'avatar' => ['nullable', 'image', 'max:5120'], // max 5MB
        ], [
            'name.required' => 'Vui lòng nhập họ và tên cơ thủ!',
            'username.required' => 'Vui lòng nhập username duy nhất!',
            'username.alpha_dash' => 'Username chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới!',
            'username.unique' => 'Username này đã được sử dụng!',
            'email.required' => 'Vui lòng nhập địa chỉ email!',
            'email.unique' => 'Email này đã tồn tại trong hệ thống!',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên!',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $password = ! empty($validated['password']) ? Hash::make($validated['password']) : Hash::make('password');

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'],
            'password' => $password,
            'role' => $validated['role'] ?? 'member',
            'avatar_path' => $avatarPath,
            'is_active' => true,
            'telegram_link_code' => Str::random(10),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã tạo cơ thủ {$user->name} (@{$user->username}) thành công!",
                'user' => $user,
            ]);
        }

        return back()->with('success', "Đã tạo mới cơ thủ {$user->name} (@{$user->username}) thành công! Mật khẩu khởi tạo: ".(! empty($validated['password']) ? '(đã nhập)' : 'password'));
    }

    public function toggleUserStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể tự vô hiệu hóa tài khoản của chính mình!');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $statusStr = $user->is_active ? 'kích hoạt' : 'vô hiệu hóa';

        return back()->with('success', "Đã {$statusStr} tài khoản {$user->name}.");
    }

    public function toggleUserRole(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể tự thay đổi quyền hạn của chính mình!');
        }

        $newRole = $user->role === 'admin' ? 'member' : 'admin';
        $user->update(['role' => $newRole]);

        return back()->with('success', "Đã cập nhật vai trò của {$user->name} thành {$newRole}.");
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã đặt lại mật khẩu cho cơ thủ {$user->name} (@{$user->username}) thành công!",
            ]);
        }

        return back()->with('success', "Đã đặt lại mật khẩu cho cơ thủ {$user->name} (@{$user->username}) thành công!");
    }

    public function updateBotConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_token' => ['nullable', 'string'],
            'bot_username' => ['nullable', 'string'],
            'group_chat_id' => ['nullable', 'string'],
        ]);

        $config = TelegramBotConfig::current();
        $config->update($validated);

        if ($request->boolean('set_webhook') && $config->bot_token) {
            $webhookUrl = route('telegram.webhook');
            $result = $this->telegramBot->setWebhook($webhookUrl);

            if (! empty($result['ok'])) {
                $config->update([
                    'webhook_url' => $webhookUrl,
                    'last_webhook_ping_at' => now(),
                ]);

                return back()->with('success', 'Cập nhật cấu hình và đăng ký Webhook Telegram thành công!');
            }

            return back()->with('warning', 'Đã lưu cấu hình nhưng Telegram báo lỗi: '.($result['description'] ?? 'Không rõ nguyên nhân'));
        }

        return back()->with('success', 'Đã lưu cấu hình Telegram Bot.');
    }

    public function sendDailyReport(BilliardStatsService $statsService): RedirectResponse
    {
        $result = $this->telegramBot->sendDailyReport($statsService);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
        ], [
            'logo.required' => 'Vui lòng chọn file hình ảnh logo!',
            'logo.mimes' => 'Logo chỉ chấp nhận định dạng: PNG, JPG, JPEG, SVG hoặc WebP!',
            'logo.max' => 'Dung lượng logo không được vượt quá 5MB!',
        ]);

        $oldLogo = Setting::get('app_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        Setting::set('app_logo', $path);

        return back()->with('success', 'Đã cập nhật logo hệ thống thành công!');
    }

    public function removeLogo(): RedirectResponse
    {
        $oldLogo = Setting::get('app_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        Setting::forget('app_logo');

        return back()->with('success', 'Đã xóa logo tùy chỉnh. Hệ thống chuyển về logo mặc định!');
    }
}
