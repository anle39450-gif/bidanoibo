<?php

namespace Database\Factories;

use App\Models\BilliardMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BilliardMatch>
 */
class BilliardMatchFactory extends Factory
{
    protected $model = BilliardMatch::class;

    public function definition(): array
    {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        return [
            'creator_id' => $player1->id,
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'winner_id' => $player1->id,
            'loser_id' => $player2->id,
            'played_at' => now(),
            'prize' => 'Kèo cà phê muối',
            'notes' => fake()->sentence(),
            'status' => 'pending',
            'notified_at' => now(),
            'auto_confirm_at' => now()->addHours(24),
            'created_from' => 'web',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => 'opponent',
        ]);
    }

    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disputed',
            'disputed_at' => now(),
            'dispute_reason' => 'Kết quả khai báo không đúng!',
        ]);
    }
}
