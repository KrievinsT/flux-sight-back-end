<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Role;
use App\Models\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Storage>
 */
class StorageFactory extends Factory
{
    protected $model = Storage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => $this->faker->url(),
            'seo' => $this->faker->sentence(),
            'page_speed' => $this->faker->randomFloat(2, 1, 100),
            'is_active' => $this->faker->boolean(),
            'role_id' => 1,
        ];
    }
}
