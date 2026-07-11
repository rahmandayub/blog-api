<?php

use App\Models\Post;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('blog.api_key', 'test-api-key-value');
});

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/posts')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Missing API key']);
});

test('invalid api key returns 401', function () {
    $this->getJson('/api/posts', [
        'Authorization' => 'Bearer invalid-key',
    ])->assertUnauthorized()
        ->assertJson(['message' => 'Invalid API key']);
});

test('valid api key returns 200 on posts list', function () {
    $this->getJson('/api/posts', [
        'Authorization' => 'Bearer test-api-key-value',
    ])->assertSuccessful();
});

test('valid api key returns 200 on single post', function () {
    Http::fake();
    $post = Post::factory()->create();

    $this->getJson('/api/posts/'.$post->slug, [
        'Authorization' => 'Bearer test-api-key-value',
    ])->assertSuccessful();
});

test('single post returns 404 with valid key for nonexistent slug', function () {
    $this->getJson('/api/posts/nonexistent-slug', [
        'Authorization' => 'Bearer test-api-key-value',
    ])->assertNotFound();
});

test('valid api key returns 200 on categories list', function () {
    $this->getJson('/api/categories', [
        'Authorization' => 'Bearer test-api-key-value',
    ])->assertSuccessful();
});

test('webhook secret cannot access api routes', function () {
    $this->getJson('/api/posts', [
        'Authorization' => 'Bearer '.config('blog.webhook_secret'),
    ])->assertUnauthorized();
});
