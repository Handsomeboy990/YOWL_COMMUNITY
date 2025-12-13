<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Création d'admins
        $admins = [
                [
                    'username' => 'admin1',
                    'fullname' => 'Admin One',
                    'email' => 'admin1@yowl.fr',
                    'password' => Hash::make('admin123'),
                    'birthdate' => '1990-01-01',
                    'email_verification_code' => null,
                    'email_verification_expires_at' => null,
                    'email_verified_at' => now(),
                ],
                [
                    'username' => 'admin2',
                    'fullname' => 'Admin Two',
                    'email' => 'admin2@yowl.fr',
                    'password' => Hash::make('admin123'),
                    'birthdate' => '1992-02-02',
                    'email_verification_code' => null,
                    'email_verification_expires_at' => null,
                    'email_verified_at' => now(),
                ],
        ];
        foreach ($admins as $data) {
            $user = User::create($data);
            $user->assignRole('admin');
        }

        // Création de 50 utilisateurs classiques
        for ($i = 1; $i <= 50; $i++) {
            $data = [
                'username' => 'user' . $i,
                'fullname' => 'User ' . $i,
                'email' => 'user' . $i . '@yowl.fr',
                'password' => Hash::make('user123'),
                'birthdate' => '2000-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verification_code' => null,
                'email_verification_expires_at' => null,
                'email_verified_at' => now(),
            ];
            $user = User::create($data);
            $user->assignRole('client');
        }
    }
}
