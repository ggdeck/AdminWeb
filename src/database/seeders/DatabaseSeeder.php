<?php

namespace Database\Seeders;

use App\Models\Superadmin; // ⬅️ ganti modelnya
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Generate 10 superadmins dummy
        // Superadmin::factory(10)->create();

        Superadmin::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'), // ⬅️ biar bisa login
        ]);
    }
}
