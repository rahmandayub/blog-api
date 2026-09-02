<?php

use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('policy allows anyone to viewAny and view', function () {
    $policy = new PostPolicy;
    $user = User::factory()->create();
    $post = Post::factory()->create();

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $post))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

test('policy allows owner to update', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->update($owner, $post))->toBeTrue();
});

test('policy denies non-owner to update', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->update($other, $post))->toBeFalse();
});

test('policy allows owner to delete', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->delete($owner, $post))->toBeTrue();
});

test('policy denies non-owner to delete', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->delete($other, $post))->toBeFalse();
});

test('policy allows owner to restore and forceDelete', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->restore($owner, $post))->toBeTrue()
        ->and($policy->forceDelete($owner, $post))->toBeTrue();
});

test('policy denies non-owner to restore and forceDelete', function () {
    $policy = new PostPolicy;
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    expect($policy->restore($other, $post))->toBeFalse()
        ->and($policy->forceDelete($other, $post))->toBeFalse();
});
