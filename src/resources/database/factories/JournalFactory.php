<?php

namespace AnourValar\EloquentJournal\resources\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(config('eloquent_journal.type'));
        shuffle($types);

        $events = array_keys(config('eloquent_journal.event'));
        shuffle($events);

        return [
            'user_id' => function (array $attributes) {
                $class = config('auth.providers.users.model');
                return $class::factory()->create();
            },
            'ip_address' => $this->faker->ipv4(),
            'entity' => null,
            'entity_id' => null,
            'type' => $types[0],
            'event' => $events[0],
            'data' => null,
            'success' => $this->faker->boolean(),
            'tags' => [$this->faker->sha1()],
            'created_at' => $this->faker->dateTimeBetween('-6 months'),
        ];
    }

    /**
     * From existing users
     *
     * @return static
     */
    public function existingUser()
    {
        $users = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            $class = config('auth.providers.users.model');
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($class))) {
                $user = $class::withTrashed();
            } else {
                $user = $class::query();
            }

            return $user->get(['id']);
        });

        return $this->state(function (array $attributes) use ($users) {
            return [
                'user_id' => function (array $attributes) use ($users) {
                    return $users->shuffle()->first();
                },
            ];
        });
    }
}
