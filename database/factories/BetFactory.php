<?php

namespace Database\Factories;

use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class BetFactory extends Factory
{
    protected $model = Bet::class;

    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory()->contentManager(),

            'supervisor_user_id' => null,

            'status' => BetStatus::DRAFT,

            'source_locale' => 'en',

            'image_path' => null,

            'finish_at' => fake()->dateTimeBetween(
                '+1 day',
                '+6 months'
            ),

            'published_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'resolved_at' => null,

            'winning_answer_id' => null,
        ];
    }
}
