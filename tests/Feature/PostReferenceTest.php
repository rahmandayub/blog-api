<?php

use App\Models\Post;
use App\Models\PostReference;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('blog.api_key', 'test-api-key-value');
    Http::fake();
});

function referenceApiHeaders(): array
{
    return [
        'Authorization' => 'Bearer test-api-key-value',
        'Accept' => 'application/json',
    ];
}

test('post has many references ordered by sort_order', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    $second = PostReference::factory()->create(['post_id' => $post->id, 'sort_order' => 2, 'title' => 'Second']);
    $first = PostReference::factory()->create(['post_id' => $post->id, 'sort_order' => 1, 'title' => 'First']);

    $references = $post->refresh()->references;

    expect($references)->toHaveCount(2)
        ->and($references->first()->id)->toBe($first->id)
        ->and($references->last()->id)->toBe($second->id);
});

test('reference belongs to post', function () {
    $post = Post::factory()->create();
    $reference = PostReference::factory()->create(['post_id' => $post->id]);

    expect($reference->post->id)->toBe($post->id);
});

test('deleting post cascades its references', function () {
    $post = Post::factory()->create();
    PostReference::factory()->count(3)->create(['post_id' => $post->id]);

    expect(PostReference::where('post_id', $post->id)->count())->toBe(3);

    $post->delete();

    expect(PostReference::where('post_id', $post->id)->count())->toBe(0);
});

test('posts show returns references with correct structure and order', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    PostReference::factory()->create([
        'post_id' => $post->id,
        'title' => 'Second Ref',
        'url' => 'https://example.com/second',
        'source' => 'Example',
        'sort_order' => 2,
    ]);
    PostReference::factory()->create([
        'post_id' => $post->id,
        'title' => 'First Ref',
        'url' => 'https://example.com/first',
        'source' => 'Example',
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/posts/'.$post->slug, referenceApiHeaders())->assertSuccessful();

    $response->assertJsonStructure([
        'data' => ['references' => ['*' => ['id', 'title', 'url', 'source', 'sort_order']]],
    ]);

    $references = $response->json('data.references');
    expect($references)->toHaveCount(2)
        ->and($references[0]['title'])->toBe('First Ref')
        ->and($references[1]['title'])->toBe('Second Ref');
});

test('posts show returns empty references when none exist', function () {
    $post = Post::factory()->create(['status' => 'publish']);

    $response = $this->getJson('/api/posts/'.$post->slug, referenceApiHeaders())->assertSuccessful();

    expect($response->json('data.references'))->toBe([]);
});

test('posts index includes references', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    PostReference::factory()->create(['post_id' => $post->id]);

    $response = $this->getJson('/api/posts', referenceApiHeaders())->assertSuccessful();

    $response->assertJsonStructure([
        'data' => ['*' => ['id', 'references']],
    ]);

    $first = collect($response->json('data'))->firstWhere('id', $post->id);
    expect($first['references'])->toHaveCount(1);
});
