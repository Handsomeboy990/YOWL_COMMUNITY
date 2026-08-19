<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * RoleSeeder and PermissionSeeder create what the application needs to
     * function. UsersSeeder creates the administrator. CommunitySeeder fills
     * the platform with a demonstration community and is the only one that
     * writes content.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UsersSeeder::class,
            TagSeeder::class,
            LegalPageSeeder::class,
            CommunitySeeder::class,
        ]);
    }
}
