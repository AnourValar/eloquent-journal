<?php

namespace AnourValar\EloquentJournal\Tests\Factories;

use AnourValar\EloquentJournal\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\AnourValar\EloquentJournal\Tests\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\AnourValar\EloquentJournal\Tests\Models\User>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('79#########'),
        ];
    }
}
