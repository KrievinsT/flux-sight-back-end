<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class Roles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::factory()->create(['name' => 'Admin']);
        Role::factory()->create(['name' => 'Editor']);
        Role::factory()->create(['name' => 'Viewer']);
    }
}
