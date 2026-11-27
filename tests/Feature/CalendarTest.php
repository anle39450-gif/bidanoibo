<?php

use App\Livewire\MatchCalendar;
use App\Models\BilliardMatch;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('guest is redirected to login when accessing calendar route', function () {
    $response = $this->get('/calendar');
    $response->assertRedirect(route('login'));
});

test('authenticated user can access calendar route', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/calendar');
    $response->assertStatus(200);
    $response->assertSeeText('Theo ngày');
});

test('match calendar renders current month and selects date with matches', function () {
    $player1 = User::factory()->create(['name' => 'Nguyễn Văn An']);
    $player2 = User::factory()->create(['name' => 'Trần Văn Bình']);

    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'prize' => 'Kèo cà phê muối',
        'played_at' => Carbon::today(),
    ]);

    Livewire::actingAs($player1)
        ->test(MatchCalendar::class)
        ->assertSee('Kèo cà phê muối')
        ->assertSee('Nguyễn Văn An')
        ->assertSee('Trần Văn Bình')
        ->call('goToPrevMonth')
        ->assertStatus(200)
        ->call('goToNextMonth')
        ->assertStatus(200)
        ->call('goToToday')
        ->assertStatus(200);
});

test('matches page allows toggling between list and calendar views for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/matches?view=calendar')
        ->assertStatus(200)
        ->assertSee('Tháng');

    $this->actingAs($user)
        ->get('/matches?view=list')
        ->assertStatus(200)
        ->assertSee('Trạng thái');
});
