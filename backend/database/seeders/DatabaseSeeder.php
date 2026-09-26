<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo data. Password for all accounts: "password".
     * Realtors: emma.carter@example.com, liam.brooks@example.com, … (see HomelySeeder).
     */
    public function run(): void
    {
        User::factory()->create(['name' => 'Demo Client', 'email' => 'client@example.com']);
        User::factory()->admin()->create(['name' => 'Demo Admin', 'email' => 'admin@example.com']);

        $this->call(HomelySeeder::class);
    }
}
