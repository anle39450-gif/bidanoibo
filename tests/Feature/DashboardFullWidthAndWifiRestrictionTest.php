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

test('wifi restriction component renders when enabled and does NOT expose the web ip in plaintext', function () {
    config([
        'app.wifi_restriction_enabled' => true,
        'app.allowed_wifi_ip' => '222.252.18.26',
    ]);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('wifiRestrictionOverlay');
    // Ensure the web's private IP is completely hidden and NOT displayed in HTML
    $response->assertDontSee('222.252.18.26');
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

test('wifi verify endpoint correctly validates ip match without revealing target ip', function () {
    config([
        'app.allowed_wifi_ip' => '222.252.18.26',
    ]);

    // Matching IP
    $resValid = $this->postJson(route('wifi.verify'), ['ip' => '222.252.18.26']);
    $resValid->assertOk();
    $resValid->assertJson(['allowed' => true]);

    // Mismatched IP
    $resInvalid = $this->postJson(route('wifi.verify'), ['ip' => '1.2.3.4']);
    $resInvalid->assertOk();
    $resInvalid->assertJson(['allowed' => false]);
});
