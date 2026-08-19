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
     * Seed the administrator account, and only that one.
     *
     * The members come from CommunitySeeder, which gives them names, ages and
     * content. This seeder used to create fifty accounts called user1 to
     * user50 sharing one password written in the file.
     *
     * No password is written here either: SEED_ADMIN_PASSWORD is used when it
     * is set, otherwise one is generated and printed once.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'UsersSeeder refuses to run in production. '
                .'Create the first administrator with php artisan yowl:make-admin instead.'
            );
        }

        $email = env('SEED_ADMIN_EMAIL', 'admin@yowl.local');
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(20);

        $admin = User::create([
            'username' => 'admin',
            'fullname' => 'Administrateur YOWL',
            'email' => $email,
            'password' => Hash::make($password),
            'birthdate' => '1990-01-01',
        ]);
        // email_verified_at n'est pas assignable en masse, volontairement.
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('admin');

        $this->command?->newLine();
        $this->command?->info('Administrateur, affiché une seule fois :');
        $this->command?->line('  '.$email.'  '.$password);
    }
}
