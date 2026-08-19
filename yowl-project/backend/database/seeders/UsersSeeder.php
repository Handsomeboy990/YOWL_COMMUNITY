<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use RuntimeException;

class UsersSeeder extends Seeder
{
    /**
     * The number of demonstration members created alongside the administrator.
     */
    private const DEMO_MEMBERS = 50;

    /**
     * Seed one administrator and a set of demonstration members.
     *
     * No password is written in this file. The administrator password comes
     * from SEED_ADMIN_PASSWORD, and a random one is generated and printed once
     * when the variable is absent. Demonstration members share a generated
     * password that is printed the same way, so a seeded database never ships
     * with a credential anybody can read in the repository.
     */
    public function run(): void
    {
        $this->guardAgainstProduction();

        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@yowl.local');
        $adminPassword = env('SEED_ADMIN_PASSWORD') ?: Str::password(20);
        $memberPassword = env('SEED_MEMBER_PASSWORD') ?: Str::password(20);

        $admin = User::create([
            'username' => 'admin',
            'fullname' => 'Administrateur YOWL',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'birthdate' => '1990-01-01',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        for ($i = 1; $i <= self::DEMO_MEMBERS; $i++) {
            $member = User::create([
                'username' => 'user'.$i,
                'fullname' => 'User '.$i,
                'email' => 'user'.$i.'@yowl.local',
                'password' => Hash::make($memberPassword),
                'birthdate' => '2000-01-'.str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
            ]);
            $member->assignRole('client');
        }

        $this->announce($adminEmail, $adminPassword, $memberPassword);
    }

    /**
     * Refuse to seed a production database.
     *
     * Seeding creates accounts with known addresses and resets nothing else,
     * so running it against live data is never intentional.
     */
    private function guardAgainstProduction(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'UsersSeeder refuses to run in production. '
                .'Create the first administrator with a dedicated command instead.'
            );
        }
    }

    /**
     * Print the generated credentials once, so they can be used and forgotten.
     */
    private function announce(string $adminEmail, string $adminPassword, string $memberPassword): void
    {
        $this->command?->newLine();
        $this->command?->info('Seeded credentials, shown once:');
        $this->command?->line('  administrator  '.$adminEmail.'  '.$adminPassword);
        $this->command?->line('  members        user1@yowl.local ... user'.self::DEMO_MEMBERS.'@yowl.local  '.$memberPassword);
        $this->command?->newLine();
    }
}
