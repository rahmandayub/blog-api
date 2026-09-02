<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('user manage list displays users', function () {
    $user = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $this->artisan('user:manage', ['action' => 'list'])
        ->assertSuccessful()
        ->expectsTable(['ID', 'Name', 'Email', 'Created At'], [
            [$user->id, 'Alice', 'alice@example.com', $user->created_at->toDateTimeString()],
        ]);
});

test('user manage remove deletes user with confirmation', function () {
    $user = User::factory()->create();

    $this->artisan('user:manage', ['action' => 'remove', 'user_id' => $user->id])
        ->expectsConfirmation("Are you sure you want to delete user {$user->name} ({$user->email})?", 'yes')
        ->assertSuccessful();

    expect(User::find($user->id))->toBeNull();
});

test('user manage remove cancels when not confirmed', function () {
    $user = User::factory()->create();

    $this->artisan('user:manage', ['action' => 'remove', 'user_id' => $user->id])
        ->expectsConfirmation("Are you sure you want to delete user {$user->name} ({$user->email})?", 'no')
        ->assertSuccessful();

    expect(User::find($user->id))->not->toBeNull();
});

test('user manage list works with no users', function () {
    User::query()->delete();

    $this->artisan('user:manage', ['action' => 'list'])
        ->assertSuccessful();
});

test('user manage invalid action shows error', function () {
    $this->artisan('user:manage', ['action' => 'invalid'])
        ->assertSuccessful()
        ->expectsOutputToContain('Invalid action');
});

test('user manage remove with non-existent id shows error', function () {
    $this->artisan('user:manage', ['action' => 'remove', 'user_id' => 9999])
        ->assertSuccessful()
        ->expectsOutputToContain('User not found');
});
