<?php

use App\Jobs\SendWebhookJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Config::set('blog.webhook_url', 'https://example.com/webhook');
    Config::set('blog.webhook_secret', 'test-webhook-secret');
});

test('job sends webhook with correct url, method, headers and payload', function () {
    Http::fake();

    $job = new SendWebhookJob('post.updated', ['id' => 1, 'title' => 'Test']);
    $job->handle();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-webhook-secret')
            && $request['event'] === 'post.updated'
            && $request['data']['id'] === 1
            && $request['data']['title'] === 'Test';
    });
});

test('job does nothing if webhook_url is null', function () {
    Http::fake();
    Config::set('blog.webhook_url', null);

    $job = new SendWebhookJob('post.updated', ['id' => 1]);
    $job->handle();

    Http::assertNothingSent();
});

test('job does nothing if webhook_url is empty string', function () {
    Http::fake();
    Config::set('blog.webhook_url', '');

    $job = new SendWebhookJob('post.updated', ['id' => 1]);
    $job->handle();

    Http::assertNothingSent();
});

test('job throws on http failure', function () {
    expect(config('blog.webhook_url'))->toBe('https://example.com/webhook');

    // Use fakeSequence to ensure 500 response — avoids beforeEach override issue
    Http::fakeSequence()
        ->push('Server Error', 500);

    $job = new SendWebhookJob('post.updated', ['id' => 1]);

    expect(fn () => $job->handle())->toThrow(RequestException::class);

    Http::assertSentCount(1);
});

test('job has correct tries and backoff', function () {
    $job = new SendWebhookJob('post.updated', ['id' => 1]);

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([5, 15]);
});

test('job failed logs error', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Webhook failed')
                && $context['event'] === 'post.updated';
        });

    $job = new SendWebhookJob('post.updated', ['id' => 1]);
    $job->failed(new Exception('Test failure'));
});

test('post webhook payload contains all required fields per WEBHOOK_SPEC', function () {
    Http::fake();
    // Ensure config is set (beforeEach already does)
    expect(config('blog.webhook_url'))->toBe('https://example.com/webhook');

    // Create a post with relations — observer will dispatch webhook
    $post = Post::factory()->create(['status' => 'publish']);
    $post->tags()->attach(Tag::factory()->create());

    $recorded = Http::recorded();
    expect($recorded->count())->toBeGreaterThan(1);
    $postRequest = $recorded->first(function ($pair) {
        return $pair[0]['event'] === 'post.updated';
    });
    expect($postRequest)->not->toBeNull();
    $postData = $postRequest[0]['data'];
    expect($postData)->toHaveKeys(['id', 'title', 'slug', 'content', 'excerpt', 'featured_image', 'status', 'published_at', 'created_at', 'updated_at', 'user', 'category', 'tags']);
    expect($postData['id'])->toBe($post->id);
    expect($postData['excerpt'])->toBeString();
    expect($postData['created_at'])->toBeString();
    expect($postData['updated_at'])->toBeString();
    expect($postData['user'])->toHaveKeys(['id', 'name', 'email', 'bio', 'profile_photo']);
    expect($postData['category'])->toHaveKeys(['id', 'name']);
    expect($postData['tags'])->toBeArray();
});

test('category and tag webhooks contain correct minimal payload', function () {
    Http::fake();

    $category = Category::factory()->create();
    Http::assertSent(function ($request) use ($category) {
        return $request['event'] === 'category.updated'
            && $request['data']['id'] === $category->id
            && $request['data']['name'] === $category->name
            && $request['data']['slug'] === $category->slug;
    });

    Http::fake();
    $tag = Tag::factory()->create();
    Http::assertSent(function ($request) use ($tag) {
        return $request['event'] === 'tag.updated'
            && $request['data']['id'] === $tag->id
            && $request['data']['name'] === $tag->name
            && $request['data']['slug'] === $tag->slug;
    });
});
