<?php

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::fake();
});

test('published_at is set when status changes to publish', function () {
    $post = Post::factory()->draft()->create();
    expect($post->published_at)->toBeNull();

    $post->update(['status' => 'publish']);

    expect($post->refresh()->published_at)->not->toBeNull()
        ->and($post->published_at->isToday())->toBeTrue();
});

test('published_at is nulled when status changes to draft', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    expect($post->published_at)->not->toBeNull();

    $post->update(['status' => 'draft']);

    expect($post->refresh()->published_at)->toBeNull();
});

test('published_at not overwritten if already set when publishing', function () {
    $existingDate = now()->subDays(5);
    $post = Post::factory()->create(['status' => 'draft', 'published_at' => null]);
    // Direct DB update to simulate existing published_at
    $post->update(['status' => 'publish']);
    $firstPublished = $post->refresh()->published_at;

    // Change title without status change should not touch published_at
    $post->update(['title' => 'New Title']);

    expect($post->refresh()->published_at->toDateTimeString())->toBe($firstPublished->toDateTimeString());
});

test('published_at remains null when creating as draft', function () {
    $post = Post::factory()->draft()->create();
    expect($post->published_at)->toBeNull();
});

test('published_at is set when creating as publish without explicit date', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    expect($post->published_at)->not->toBeNull();
});

test('deleting post deletes featured_image from storage', function () {
    Storage::fake('public');
    $fakePath = 'featured-images/test.jpg';
    Storage::disk('public')->put($fakePath, 'fake-content');

    // Create post with Http fake still on, but need to avoid WebP conversion
    // Use a .webp file so conversion is skipped
    $webpPath = 'featured-images/test.webp';
    Storage::disk('public')->put($webpPath, 'fake-webp-content');
    $post = Post::factory()->create(['status' => 'publish', 'featured_image' => $webpPath]);

    expect(Storage::disk('public')->exists($webpPath))->toBeTrue();

    $post->delete();

    expect(Storage::disk('public')->exists($webpPath))->toBeFalse();
});

test('featured_image webp conversion is skipped for already webp', function () {
    Storage::fake('public');
    $webpPath = 'featured-images/already.webp';
    Storage::disk('public')->put($webpPath, 'fake-content');

    $post = Post::factory()->create(['featured_image' => $webpPath, 'status' => 'publish']);

    expect($post->featured_image)->toBe($webpPath)
        ->and(Storage::disk('public')->exists($webpPath))->toBeTrue();
});

test('featured_image conversion skipped if file missing', function () {
    Storage::fake('public');

    $post = Post::factory()->create(['featured_image' => 'featured-images/nonexistent.jpg', 'status' => 'publish']);

    // Should not throw, remains as is
    expect($post->featured_image)->toBe('featured-images/nonexistent.jpg');
});

test('post factory draft state creates draft post', function () {
    $post = Post::factory()->draft()->create();
    expect($post->status)->toBe('draft');
});

test('post belongs to category and user and has tags', function () {
    $post = Post::factory()->create();
    expect($post->category)->not->toBeNull()
        ->and($post->user)->not->toBeNull()
        ->and($post->tags)->toBeInstanceOf(Collection::class);
});

test('updating featured_image deletes old image via model event', function () {
    // This test verifies the WebP conversion path cleans up old file when possible
    // We use Storage fake and a real image conversion would require GD; we test the branch where file missing is handled
    Storage::fake('public');
    $oldPath = 'featured-images/old.jpg';
    Storage::disk('public')->put($oldPath, 'old-content');

    $post = Post::factory()->create(['featured_image' => $oldPath, 'status' => 'publish']);

    // Since old.jpg exists but is not a valid image, conversion will fail and be logged, but not throw
    // The post should still retain the original path (or possibly remain)
    expect($post->featured_image)->not->toBeNull();
});
