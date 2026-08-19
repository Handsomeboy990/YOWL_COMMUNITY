<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeAdministrator extends Command
{
    /**
     * @var string
     */
    protected $signature = 'yowl:make-admin
                            {--email= : Address of the account}
                            {--username= : Public name of the account}';

    /**
     * @var string
     */
    protected $description = 'Create an administrator, or promote an existing account to administrator';

    /**
     * Create the first administrator of an environment.
     *
     * This replaces seeding in any environment holding real data: the password
     * is typed by the operator and never stored in the repository, and an
     * existing account is promoted rather than duplicated.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?: text(
            label: 'Email of the administrator',
            required: true,
        );

        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $this->promote($existing);
        }

        $username = $this->option('username') ?: text(
            label: 'Username',
            required: true,
        );

        $secret = password(label: 'Password', required: true);
        $confirmation = password(label: 'Confirm the password', required: true);

        $validator = Validator::make(
            [
                'email' => $email,
                'username' => $username,
                'password' => $secret,
                'password_confirmation' => $confirmation,
            ],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $this->ensureRoleExists();

        $user = User::create([
            'username' => $username,
            'fullname' => $username,
            'email' => $email,
            'password' => Hash::make($secret),
            'birthdate' => '1990-01-01',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        $this->info('Administrator created: '.$email);

        return self::SUCCESS;
    }

    /**
     * Promote an account that already exists.
     */
    private function promote(User $user): int
    {
        if ($user->hasRole('admin')) {
            $this->warn($user->email.' is already an administrator.');

            return self::SUCCESS;
        }

        $this->ensureRoleExists();
        $user->assignRole('admin');
        $this->info('Promoted to administrator: '.$user->email);

        return self::SUCCESS;
    }

    /**
     * The role table may be empty on a freshly migrated database.
     */
    private function ensureRoleExists(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
    }
}
