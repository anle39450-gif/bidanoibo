<?php

use App\Livewire\CreateMatchModal;
use App\Livewire\RealtimeNotificationBell;
use App\Models\BilliardMatch;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\UploadedFile;
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
    $response->assertSee('Lịch Thi Đấu Bida Theo Ngày');
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
