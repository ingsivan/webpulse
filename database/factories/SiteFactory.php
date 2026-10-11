<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => 'https://'.fake()->domainName(),
            'is_monitored' => false,
            'check_interval_minutes' => 60,
        ];
    }

    public function monitored(): static
    {
        return $this->state([
            'user_id' => User::factory(),
            'is_monitored' => true,
        ]);
    }
}
