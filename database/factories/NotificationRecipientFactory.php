<?php

namespace Database\Factories;

use App\Models\NotificationRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationRecipient>
 */
class NotificationRecipientFactory extends Factory
{
    protected $model = NotificationRecipient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'role_tag' => null,
            'notes' => null,
        ];
    }

    public function helpdesk(): static
    {
        return $this->state(fn (): array => [
            'name' => 'IT Helpdesk',
            'email' => 'helpdesk@example.com',
            'role_tag' => 'it_helpdesk',
        ]);
    }
}
