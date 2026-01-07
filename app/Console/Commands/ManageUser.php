<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;
use function Laravel\Prompts\confirm;

class ManageUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage {action? : The action to perform (list, create, edit, remove)} {user_id? : The ID of the user to edit or remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage users: list, create, edit, and remove.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (! $action) {
            $action = select(
                label: 'What would you like to do?',
                options: ['list', 'create', 'edit', 'remove'],
                default: 'list'
            );
        }

        match ($action) {
            'list' => $this->listUsers(),
            'create' => $this->createUser(),
            'edit' => $this->editUser(),
            'remove'  => $this->removeUser(),
            default => $this->error("Invalid action: {$action}"),
        };
    }

    protected function listUsers()
    {
        $users = User::all(['id', 'name', 'email', 'created_at']);

        $this->table(
            ['ID', 'Name', 'Email', 'Created At'],
            $users->map(fn($user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->toDateTimeString(),
            ])->toArray()
        );
    }

    protected function createUser()
    {
        $this->info('Creating a new user...');

        $name = text(
            label: 'Name',
            required: true
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: fn(string $value) => match (true) {
                !filter_var($value, FILTER_VALIDATE_EMAIL) => 'Invalid email format.',
                User::where('email', $value)->exists() => 'Email already in use.',
                default => null
            }
        );

        $pwd = password(
            label: 'Password',
            required: true,
            validate: fn(string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null
        );

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($pwd),
        ]);

        $this->info("User created successfully: {$user->name} (ID: {$user->id})");
    }

    protected function editUser()
    {
        $id = $this->argument('user_id');

        if (!$id) {
            $id = text(
                label: 'Enter the ID of the user to edit',
                required: true,
                validate: fn($value) => User::where('id', $value)->exists() ? null : 'User not found.'
            );
        }

        $user = User::find($id);

        if (!$user) {
            $this->error("User not found with ID: {$id}");
            return;
        }

        $this->info("Editing user: {$user->name} ({$user->email})");

        $action = select(
            label: 'Which field do you want to edit?',
            options: ['name', 'email', 'password', 'cancel'],
            default: 'cancel'
        );

        if ($action === 'cancel') {
            $this->info('Edit cancelled.');
            return;
        }

        if ($action === 'password') {
            $pwd = password(
                label: 'New Password',
                required: true,
                validate: fn(string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null
            );
            $user->password = Hash::make($pwd);
        } elseif ($action === 'email') {
            $newEmail = text(
                label: 'New Email',
                default: $user->email,
                required: true,
                validate: fn(string $value) => match (true) {
                    !filter_var($value, FILTER_VALIDATE_EMAIL) => 'Invalid email format.',
                    $value !== $user->email && User::where('email', $value)->exists() => 'Email already in use.',
                    default => null
                }
            );
            $user->email = $newEmail;
        } else {
            // Name
            $newName = text(
                label: 'New Name',
                default: $user->name,
                required: true
            );
            $user->name = $newName;
        }

        $user->save();
        $this->info("User updated successfully.");
    }

    protected function removeUser()
    {
        $id = $this->argument('user_id');

        if (!$id) {
            $id = text(
                label: 'Enter the ID of the user to remove',
                required: true,
                validate: fn($value) => User::where('id', $value)->exists() ? null : 'User not found.'
            );
        }

        $user = User::find($id);

        if (!$user) {
            $this->error("User not found with ID: {$id}");
            return;
        }

        $confirmed = confirm(
            label: "Are you sure you want to delete user {$user->name} ({$user->email})?",
            default: false
        );

        if ($confirmed) {
            $user->delete();
            $this->info("User deleted successfully.");
        } else {
            $this->warn("Deletion cancelled.");
        }
    }
}
