<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class Auth extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 users
        User::factory(10)->create();
    }
}
