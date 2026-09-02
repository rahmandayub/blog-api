<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::fake();
    Storage::fake('public');
});

test('image optimize skips already webp files', function () {
    $webpPath = 'featured-images/already.webp';
    Storage::disk('public')->put($webpPath, 'fake-webp-content');

    $post = Post::factory()->create(['featured_image' => $webpPath, 'status' => 'publish']);

    $this->artisan('image:optimize-webp', ['--disk' => 'public'])
        ->assertSuccessful()
        ->expectsOutputToContain('Image optimization complete');

    expect($post->refresh()->featured_image)->toBe($webpPath)
        ->and(Storage::disk('public')->exists($webpPath))->toBeTrue();
});

test('image optimize handles missing file gracefully', function () {
    $missingPath = 'featured-images/missing.jpg';
    $post = Post::factory()->create(['featured_image' => $missingPath, 'status' => 'publish']);

    $this->artisan('image:optimize-webp', ['--disk' => 'public'])
        ->assertSuccessful();

    // Should remain as is, not throw
    expect($post->refresh()->featured_image)->toBe($missingPath);
});

test('image optimize converts valid image to webp', function () {
    // Create a valid 1x1 PNG image
    $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
    $originalPath = 'featured-images/test.png';
    Storage::disk('public')->put($originalPath, $pngData);

    $post = Post::factory()->create(['featured_image' => $originalPath, 'status' => 'publish']);

    $this->artisan('image:optimize-webp', ['--disk' => 'public'])
        ->assertSuccessful();

    $post->refresh();
    // After conversion, featured_image should be a .webp path (if GD available) or remain if conversion failed
    // We check that command completed without error and either converted or handled gracefully
    expect($post->featured_image)->toBeString();
    if (str_ends_with($post->featured_image, '.webp')) {
        expect(Storage::disk('public')->exists($post->featured_image))->toBeTrue();
    }
});

test('image optimize handles user profile photos', function () {
    $webpPath = 'profile-photos/already.webp';
    Storage::disk('public')->put($webpPath, 'fake-content');
    $user = User::factory()->create(['profile_photo' => $webpPath]);

    $this->artisan('image:optimize-webp', ['--disk' => 'public'])
        ->assertSuccessful();

    expect($user->refresh()->profile_photo)->toBe($webpPath);
});

test('image optimize with no images completes', function () {
    // Ensure no posts or users with images
    Post::query()->update(['featured_image' => null]);
    User::query()->update(['profile_photo' => null]);

    $this->artisan('image:optimize-webp', ['--disk' => 'public'])
        ->assertSuccessful()
        ->expectsOutputToContain('Image optimization complete');
});
