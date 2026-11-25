<?php

use App\Models\User;

test('dashboard page renders with full width layout without max-w-7xl restriction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $content = $response->getContent();

    // Check that main element does not have max-w-7xl on dashboard
    expect($content)->toContain('<main class="flex-1 p-4 sm:p-6 lg:p-8 w-full');
    expect($content)->not->toMatch('/<main class="flex-1 p-4 sm:p-6 lg:p-8 w-full[^>]*max-w-7xl/');
});

test('other pages retain max-w-7xl container restriction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('calendar'));

    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toMatch('/<main class="flex-1 p-4 sm:p-6 lg:p-8 w-full[^>]*max-w-7xl/');
});

test('wifi restriction component renders when enabled with correct target ip 222.252.18.26 and ipify check', function () {
    config([
        'app.wifi_restriction_enabled' => true,
        'app.allowed_wifi_ip' => '222.252.18.26',
    ]);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('wifiRestrictionOverlay');
    $response->assertSee('222.252.18.26');
    $response->assertSee('https://api.ipify.org');
    $response->assertSee('Yêu Cầu Kết Nối WiFi Nội Bộ');
    $response->assertSee('performWifiCheck');
});

test('wifi restriction component does not render when disabled', function () {
    config([
        'app.wifi_restriction_enabled' => false,
    ]);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertDontSee('wifiRestrictionOverlay');
    $response->assertDontSee('Yêu Cầu Kết Nối WiFi Nội Bộ');
});
