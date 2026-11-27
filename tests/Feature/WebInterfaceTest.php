<?php

use App\Livewire\CreateMatchModal;
use App\Livewire\HeadToHeadComparator;
use App\Livewire\RealtimeNotificationBell;
use App\Models\BilliardMatch;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guest cannot view internal pages and is redirected to login', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get('/calendar')->assertRedirect(route('login'));
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->get('/matches')->assertRedirect(route('login'));
    $this->get('/leaderboard')->assertRedirect(route('login'));
    $this->get('/members')->assertRedirect(route('login'));
    $this->get('/admin')->assertRedirect(route('login'));
});

test('login page has dedicated standalone layout without sidebar', function () {
    $response = $this->get(route('login'));
    $response->assertStatus(200);
    $response->assertSee('Hệ thống quản lý lịch sử đấu bida nội bộ');
    $response->assertDontSee('Menu chính');
    $response->assertDontSee('Bảng xếp hạng');
    $response->assertDontSee('Danh sách cơ thủ');
    $response->assertDontSee('Hệ thống nội bộ chỉ dành cho thành viên');
});

test('active user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'username' => 'anbida',
        'email' => 'an@bida.local',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $response = $this->post(route('login'), [
        'login' => 'anbida',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('calendar'));
    $this->assertAuthenticatedAs($user);
});

test('inactive user cannot log in', function () {
    $inactiveUser = User::factory()->create([
        'username' => 'khoa',
        'email' => 'khoa@bida.local',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->from(route('login'))->post(route('login'), [
        'login' => 'khoa',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});

test('authenticated active member can access internal pages', function () {
    $member = User::factory()->create(['role' => 'member']);

    $this->actingAs($member)->get('/')->assertStatus(200);
    $this->actingAs($member)->get('/calendar')->assertStatus(200);
    $this->actingAs($member)->get('/dashboard')->assertStatus(200);
    $this->actingAs($member)->get('/matches')->assertStatus(200);
    $this->actingAs($member)->get('/leaderboard')->assertStatus(200);
    $this->actingAs($member)->get('/members')->assertStatus(200);
});

test('regular member cannot access admin panel', function () {
    $member = User::factory()->create(['role' => 'member']);

    $this->actingAs($member)->get(route('admin.index'))->assertStatus(403);
});

test('admin can access admin panel and resolve disputes', function () {
    $admin = User::factory()->admin()->create();
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::factory()->disputed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertStatus(200);

    $this->actingAs($admin)
        ->post(route('admin.matches.resolve', $match->id), [
            'resolution' => 'confirm_initial',
            'admin_notes' => 'Xem xét bằng chứng xác định kết quả hợp lệ.',
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->status)->toBe('resolved');
});

test('authenticated user can confirm match via web route', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'pending',
    ]);

    $this->actingAs($player2)
        ->post(route('matches.confirm', $match->id))
        ->assertRedirect();

    $match->refresh();
    expect($match->status)->toBe('confirmed');
    expect($match->confirmed_by)->toBe('opponent');
});

test('authenticated user can dispute match via web route with reason', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'pending',
    ]);

    $this->actingAs($player2)
        ->post(route('matches.dispute', $match->id), [
            'reason' => 'Ván cuối bi 8 do tôi đánh vào trước!',
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->status)->toBe('disputed');
    expect($match->dispute_reason)->toBe('Ván cuối bi 8 do tôi đánh vào trước!');
});

test('livewire notification bell renders pending matches for user', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'prize' => 'Kèo cà phê muối',
        'status' => 'pending',
    ]);

    UserNotification::create([
        'user_id' => $player2->id,
        'billiard_match_id' => $match->id,
        'type' => 'match_pending_confirmation',
        'title' => 'Trận đấu mới cần bạn xác nhận!',
        'message' => 'Bạn có 24h để phản hồi',
        'is_read' => false,
    ]);

    Livewire::actingAs($player2)
        ->test(RealtimeNotificationBell::class)
        ->assertSee('Kèo cà phê muối')
        ->assertSee('Chấp nhận');
});

test('livewire create match modal opens via event dispatch', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $player2 = User::factory()->create(['username' => 'binh']);

    Livewire::actingAs($player1)
        ->test(CreateMatchModal::class)
        ->assertSet('isOpen', false)
        ->dispatch('openCreateMatchModal')
        ->assertSet('isOpen', true)
        ->call('closeModal')
        ->assertSet('isOpen', false)
        ->dispatch('openCreateMatchModal', opponentId: $player2->id)
        ->assertSet('isOpen', true)
        ->assertSet('opponentId', $player2->id);
});

test('livewire create match modal records new match', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $player2 = User::factory()->create(['username' => 'binh']);

    Livewire::actingAs($player1)
        ->test(CreateMatchModal::class)
        ->set('opponentId', $player2->id)
        ->set('winnerChoice', 'me')
        ->set('prize', 'Kèo trà sữa trân châu')
        ->set('notes', 'Thắng 3-0 tuyệt đối')
        ->call('save')
        ->assertHasNoErrors();

    $match = BilliardMatch::where('prize', 'Kèo trà sữa trân châu')->first();
    expect($match)->not->toBeNull();
    expect($match->status)->toBe('pending');
    expect($match->winner_id)->toBe($player1->id);
    expect($match->loser_id)->toBe($player2->id);
});

test('livewire create match modal can create doubles match', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $teammate = User::factory()->create(['username' => 'binh']);
    $opponent1 = User::factory()->create(['username' => 'cuong']);
    $opponent2 = User::factory()->create(['username' => 'dung']);

    Livewire::actingAs($player1)
        ->test(CreateMatchModal::class)
        ->call('setMatchType', 'doubles')
        ->set('teammateId', $teammate->id)
        ->set('opponentId', $opponent1->id)
        ->set('opponent2Id', $opponent2->id)
        ->set('winnerChoice', 'me')
        ->set('prize', 'Kèo 4 ly cà phê muối')
        ->call('save')
        ->assertHasNoErrors();

    $match = BilliardMatch::where('prize', 'Kèo 4 ly cà phê muối')->first();
    expect($match)->not->toBeNull();
    expect($match->match_type)->toBe('doubles');
    expect($match->winner_id)->toBe($player1->id);
    expect($match->winner_partner_id)->toBe($teammate->id);
    expect($match->loser_id)->toBe($opponent1->id);
    expect($match->loser_partner_id)->toBe($opponent2->id);
});

test('ajax store route records singles match and returns json', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $player2 = User::factory()->create(['username' => 'binh']);

    $response = $this->actingAs($player1)
        ->postJson(route('matches.store'), [
            'match_type' => 'singles',
            'opponent_id' => $player2->id,
            'winner_choice' => 'me',
            'prize' => 'Kèo cà phê muối AJAX',
            'played_at' => '2026-03-10',
            'notes' => 'Tạo trận nhanh qua AJAX',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $match = BilliardMatch::where('prize', 'Kèo cà phê muối AJAX')->first();
    expect($match)->not->toBeNull();
    expect($match->winner_id)->toBe($player1->id);
    expect($match->loser_id)->toBe($player2->id);
    expect($match->played_at->toDateString())->toBe('2026-03-10');
    expect($match->evidence_path)->toBeNull();
});

test('ajax store route records doubles match and returns json', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $teammate = User::factory()->create(['username' => 'binh']);
    $opponent1 = User::factory()->create(['username' => 'cuong']);
    $opponent2 = User::factory()->create(['username' => 'dung']);

    $response = $this->actingAs($player1)
        ->postJson(route('matches.store'), [
            'match_type' => 'doubles',
            'teammate_id' => $teammate->id,
            'opponent_id' => $opponent1->id,
            'opponent2_id' => $opponent2->id,
            'winner_choice' => 'opponent',
            'prize' => 'Kèo 4 ly trà sữa AJAX',
            'notes' => 'Đánh đôi qua AJAX',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $match = BilliardMatch::where('prize', 'Kèo 4 ly trà sữa AJAX')->first();
    expect($match)->not->toBeNull();
    expect($match->match_type)->toBe('doubles');
    expect($match->winner_id)->toBe($opponent1->id);
    expect($match->winner_partner_id)->toBe($opponent2->id);
    expect($match->loser_id)->toBe($player1->id);
    expect($match->loser_partner_id)->toBe($teammate->id);
});

test('calendar page renders calendar view successfully for authenticated user', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('calendar'));
    $response->assertStatus(200);
    $response->assertSeeText('Theo ngày');
});

test('calendar data endpoint returns json for ajax month loading for authenticated user', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson(route('calendar.data', ['year' => 2026, 'month' => 11]));
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'year',
            'month',
            'monthTitle',
            'totalMonthMatches',
            'selectedDate',
            'days',
            'matchesByDate',
        ]);
});

test('admin can create a new member player', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Trần Văn Mới',
            'username' => 'vanmoi',
            'email' => 'moi@bida.local',
            'password' => 'secret123',
            'role' => 'member',
        ]);

    $response->assertRedirect();
    $newUser = User::where('username', 'vanmoi')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('Trần Văn Mới');
    expect($newUser->email)->toBe('moi@bida.local');
    expect($newUser->role)->toBe('member');
    expect($newUser->is_active)->toBeTrue();
});

test('regular member cannot create a new member player', function () {
    $member = User::factory()->create(['role' => 'member']);

    $response = $this->actingAs($member)
        ->post(route('admin.users.store'), [
            'name' => 'Người Lạ',
            'username' => 'nguoila',
            'email' => 'la@bida.local',
        ]);

    $response->assertStatus(403);
});

test('admin can upload custom logo and login page displays it', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->create('club_logo.png', 100, 'image/png');

    $response = $this->actingAs($admin)
        ->post(route('admin.logo.update'), [
            'logo' => $file,
        ]);

    $response->assertRedirect();
    $savedPath = Setting::get('app_logo');
    expect($savedPath)->not->toBeNull();
    Storage::disk('public')->assertExists($savedPath);

    // Verify login page renders custom logo image (as guest)
    $this->post('/logout');
    $loginResponse = $this->get(route('login'));
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee(asset('storage/'.$savedPath));
});

test('admin can remove custom logo and fallback to default', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->create('custom_logo.png', 100, 'image/png');
    $path = $file->store('logos', 'public');
    Setting::set('app_logo', $path);

    $response = $this->actingAs($admin)
        ->post(route('admin.logo.remove'));

    $response->assertRedirect();
    expect(Setting::get('app_logo'))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('regular member cannot update logo', function () {
    $member = User::factory()->create(['role' => 'member']);
    $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

    $response = $this->actingAs($member)
        ->post(route('admin.logo.update'), [
            'logo' => $file,
        ]);

    $response->assertStatus(403);
});

test('telegram bot help modal is included and accessible from top bar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('calendar'));

    $response->assertStatus(200);
    $response->assertSee('telegramHelpModal');
    $response->assertSee('openTelegramHelpModal()');
    $response->assertSee('Hướng Dẫn Kết Nối và Sử Dụng Telegram Bot');
});

test('user can change password with correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    $response = $this->actingAs($user)
        ->post(route('password.change'), [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('newpassword456', $user->password))->toBeTrue();
});

test('user cannot change password with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    $response = $this->actingAs($user)
        ->from(route('calendar'))
        ->post(route('password.change'), [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

    $response->assertRedirect(route('calendar'));
    $response->assertSessionHasErrors('current_password');

    $user->refresh();
    expect(Hash::check('oldpassword123', $user->password))->toBeTrue();
});

test('user cannot change password when confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    $response = $this->actingAs($user)
        ->post(route('password.change'), [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'mismatch789',
        ]);

    $response->assertSessionHasErrors('new_password');
});

test('admin can reset any member password', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create([
        'password' => Hash::make('initial123'),
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.users.password', $member->id), [
            'password' => 'adminreset456',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $member->refresh();
    expect(Hash::check('adminreset456', $member->password))->toBeTrue();
});

test('regular member cannot reset another member password via admin route', function () {
    $member1 = User::factory()->create(['role' => 'member']);
    $member2 = User::factory()->create(['role' => 'member', 'password' => Hash::make('secret123')]);

    $response = $this->actingAs($member1)
        ->post(route('admin.users.password', $member2->id), [
            'password' => 'hackedpassword',
        ]);

    $response->assertStatus(403);
    $member2->refresh();
    expect(Hash::check('secret123', $member2->password))->toBeTrue();
});

test('change password modal is rendered on layout and profile page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('members.show', $user->username));
    $response->assertStatus(200);
    $response->assertSee('changePasswordModal');
    $response->assertSee('openChangePasswordModal()');
    $response->assertSee('Đổi Mật Khẩu');
});

test('profile head-to-head shows results after choosing an opponent', function () {
    $member = User::factory()->create();
    $opponent = User::factory()->create();

    $match = BilliardMatch::factory()->confirmed()->create([
        'creator_id' => $member->id,
        'player1_id' => $member->id,
        'player2_id' => $opponent->id,
        'winner_id' => $member->id,
        'loser_id' => $opponent->id,
    ]);

    Livewire::actingAs($member)
        ->test(HeadToHeadComparator::class, ['member' => $member])
        ->assertSee('Chọn đối thủ')
        ->assertDontSee($match->prize)
        ->set('selectedOpponentId', $opponent->id)
        ->assertSee('1 trận')
        ->assertSee($match->prize);
});

test('dashboard displays personal win and loss stats and unpaid debts for logged in user', function () {
    $user = User::factory()->create(['name' => 'Nguyễn Văn A', 'username' => 'nva']);
    $opponent = User::factory()->create(['name' => 'Trần Văn B', 'username' => 'tvb']);

    // Match 1: User won
    BilliardMatch::create([
        'creator_id' => $user->id,
        'player1_id' => $user->id,
        'player2_id' => $opponent->id,
        'winner_id' => $user->id,
        'loser_id' => $opponent->id,
        'played_at' => now()->subDay(),
        'prize' => 'Trà chanh',
        'status' => 'confirmed',
        'prize_status' => 'unpaid',
    ]);

    // Match 2: User lost and has not paid prize yet
    BilliardMatch::create([
        'creator_id' => $opponent->id,
        'player1_id' => $opponent->id,
        'player2_id' => $user->id,
        'winner_id' => $opponent->id,
        'loser_id' => $user->id,
        'played_at' => now(),
        'prize' => 'Kèo cà phê muối',
        'status' => 'confirmed',
        'prize_status' => 'unpaid',
    ]);

    BilliardMatch::factory()->confirmed()->create([
        'creator_id' => $opponent->id,
        'player1_id' => $opponent->id,
        'player2_id' => $user->id,
        'winner_id' => $opponent->id,
        'loser_id' => $user->id,
        'prize' => 'Kèo trà sữa đã trả',
        'prize_status' => 'paid',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Trận thắng');
    $response->assertSee('Trận thua');
    $response->assertSee('Kèo cần trả');
    $response->assertSee('Kèo cà phê muối');
    $response->assertSee('Trả kèo ngay');
    $response->assertSeeInOrder(['Kèo cần trả', 'Trả kèo ngay', 'Đã trả · chờ xác nhận', 'Kèo trà sữa đã trả', 'Cập nhật trả kèo']);
});

test('dashboard shows matches awaiting the signed-in player confirmation', function () {
    $this->freezeSecond();

    $creator = User::factory()->create();
    $opponent = User::factory()->create();

    $match = BilliardMatch::factory()->create([
        'creator_id' => $creator->id,
        'player1_id' => $creator->id,
        'player2_id' => $opponent->id,
        'winner_id' => $creator->id,
        'loser_id' => $opponent->id,
        'auto_confirm_at' => now()->addHours(2)->addMinutes(30),
    ]);

    $this->actingAs($opponent)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Trận cần bạn xác nhận')
        ->assertSee('Còn 2 giờ 30 phút')
        ->assertSee(route('matches.show', $match));

    $this->actingAs($creator)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Trận cần bạn xác nhận');
});

test('dashboard paginates settled prizes and recent matches independently', function () {
    $viewer = User::factory()->create();
    $winner = User::factory()->create();

    $matches = BilliardMatch::factory()->confirmed()->count(11)->create([
        'creator_id' => $winner->id,
        'player1_id' => $winner->id,
        'player2_id' => $viewer->id,
        'winner_id' => $winner->id,
        'loser_id' => $viewer->id,
        'played_at' => now(),
        'prize_status' => 'paid',
    ]);

    $this->actingAs($viewer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('awaiting_page=2#keo-da-tra', false)
        ->assertSee('recent_page=2#tran-gan-day', false);

    $response = $this->get(route('dashboard', ['awaiting_page' => 999, 'recent_page' => 1]));

    $response->assertOk()
        ->assertViewHas('matchesAwaitingReceipt', fn (LengthAwarePaginator $paginator): bool => $paginator->currentPage() === 3
            && $paginator->perPage() === 5
            && $paginator->total() === 11
            && $paginator->getCollection()->modelKeys() === [$matches->first()->id])
        ->assertViewHas('recentMatches', fn (LengthAwarePaginator $paginator): bool => $paginator->currentPage() === 1
            && $paginator->count() === 8
            && $paginator->getCollection()->first()->id === $matches->last()->id);

    $this->get(route('dashboard', ['recent_page' => 999]))
        ->assertOk()
        ->assertViewHas('recentMatches', fn (LengthAwarePaginator $paginator): bool => $paginator->currentPage() === 2
            && $paginator->count() === 3
            && $paginator->getCollection()->last()->id === $matches->first()->id);
});
