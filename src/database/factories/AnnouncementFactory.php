<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        $md = $this->faker->paragraph();

        return [
            'title'         => $this->faker->sentence(6),
            'body_markdown' => $md,
            'body_html'     => '<p>' . e($md) . '</p>',
            'priority'      => 'normal',
            'is_pinned'     => false,
            'everyone'      => true,
            'show_on_login' => false,
            'status'        => 'published',
            'publish_at'    => now()->subHour(),
            'expires_at'    => null,
            'created_by'    => User::factory(),
            'updated_by'    => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft']);
    }

    public function archived(): self
    {
        return $this->state(['status' => 'archived']);
    }

    public function critical(): self
    {
        return $this->state(['priority' => 'critical']);
    }

    public function loginVisible(): self
    {
        return $this->state(['show_on_login' => true]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function scheduled(): self
    {
        return $this->state(['publish_at' => now()->addDay()]);
    }
}
