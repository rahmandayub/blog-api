<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

function assertWebhookRequest($request, string $expectedEvent, array $expectedData): void
{
    expect($request->url())->toBe(config('blog.webhook_url'));
    expect($request->method())->toBe('POST');
    expect($request->isJson())->toBeTrue();
    expect($request->header('Authorization'))->toContain('Bearer '.config('blog.webhook_secret'));
    expect($request['event'])->toBe($expectedEvent);

    foreach ($expectedData as $key => $value) {
        expect($request['data'][$key])->toBe($value);
    }
}

test('post created dispatches post.updated webhook with full payload', function () {
    $post = Post::factory()->create();

    Http::assertSent(function ($request) use ($post) {
        if ($request['event'] !== 'post.updated') {
            return false;
        }

        assertWebhookRequest($request, 'post.updated', [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'featured_image' => $post->featured_image,
        ]);

        // Verify nested relations
        expect($request['data']['user']['id'])->toBe($post->user->id);
        expect($request['data']['user']['name'])->toBe($post->user->name);
        expect($request['data']['category']['id'])->toBe($post->category->id);
        expect($request['data']['category']['name'])->toBe($post->category->name);
        expect($request['data']['tags'])->toBeArray();

        // Verify derived fields
        expect($request['data']['excerpt'])->toBeString();
        expect($request['data']['created_at'])->toBeString();
        expect($request['data']['updated_at'])->toBeString();

        return true;
    });
});

test('post updated dispatches post.updated webhook', function () {
    $post = Post::factory()->create();
    $post->update(['title' => 'Updated Title']);

    Http::assertSent(function ($request) {
        return $request['event'] === 'post.updated'
            && $request['data']['title'] === 'Updated Title';
    });
});

test('post deleted dispatches post.deleted webhook', function () {
    $post = Post::factory()->create();
    $postId = $post->id;
    $post->delete();

    Http::assertSent(function ($request) use ($postId) {
        if ($request['event'] !== 'post.deleted') {
            return false;
        }
        assertWebhookRequest($request, 'post.deleted', [
            'id' => $postId,
        ]);
        expect($request['data'])->toHaveKey('id');
        expect($request['data'])->not->toHaveKey('title');
        expect($request['data'])->not->toHaveKey('slug');
        expect($request['data'])->not->toHaveKey('content');
        expect($request['data'])->not->toHaveKey('user');
        expect($request['data'])->not->toHaveKey('category');
        expect($request['data'])->not->toHaveKey('tags');

        return true;
    });
});

// Post doesn't use SoftDeletes, so restore() is not available.
// The deleted event is still verified above.

test('category created dispatches category.updated webhook', function () {
    $category = Category::factory()->create();

    Http::assertSent(function ($request) use ($category) {
        if ($request['event'] !== 'category.updated') {
            return false;
        }
        assertWebhookRequest($request, 'category.updated', [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);

        return true;
    });
});

test('category updated dispatches category.updated webhook', function () {
    $category = Category::factory()->create();
    $category->update(['name' => 'Updated Category']);

    Http::assertSent(function ($request) {
        return $request['event'] === 'category.updated'
            && $request['data']['name'] === 'Updated Category';
    });
});

test('category deleted dispatches category.deleted webhook', function () {
    $category = Category::factory()->create();
    $categoryId = $category->id;
    $category->delete();

    Http::assertSent(function ($request) use ($categoryId) {
        if ($request['event'] !== 'category.deleted') {
            return false;
        }
        assertWebhookRequest($request, 'category.deleted', [
            'id' => $categoryId,
        ]);
        expect($request['data'])->toHaveKey('id');
        expect($request['data'])->not->toHaveKey('name');
        expect($request['data'])->not->toHaveKey('slug');

        return true;
    });
});

test('category restored dispatches category.updated webhook', function () {
    $category = Category::factory()->create();
    $categoryId = $category->id;
    $category->delete();
    $category->restore();

    Http::assertSent(function ($request) use ($categoryId) {
        return $request['event'] === 'category.updated'
            && $request['data']['id'] === $categoryId;
    });
});

test('tag created dispatches tag.updated webhook', function () {
    $tag = Tag::factory()->create();

    Http::assertSent(function ($request) use ($tag) {
        if ($request['event'] !== 'tag.updated') {
            return false;
        }
        assertWebhookRequest($request, 'tag.updated', [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
        ]);

        return true;
    });
});

test('tag updated dispatches tag.updated webhook', function () {
    $tag = Tag::factory()->create();
    $tag->update(['name' => 'Updated Tag']);

    Http::assertSent(function ($request) {
        return $request['event'] === 'tag.updated'
            && $request['data']['name'] === 'Updated Tag';
    });
});

test('tag deleted dispatches tag.deleted webhook', function () {
    $tag = Tag::factory()->create();
    $tagId = $tag->id;
    $tag->delete();

    Http::assertSent(function ($request) use ($tagId) {
        if ($request['event'] !== 'tag.deleted') {
            return false;
        }
        assertWebhookRequest($request, 'tag.deleted', [
            'id' => $tagId,
        ]);
        expect($request['data'])->toHaveKey('id');
        expect($request['data'])->not->toHaveKey('name');
        expect($request['data'])->not->toHaveKey('slug');

        return true;
    });
});

test('tag restored dispatches tag.updated webhook', function () {
    $tag = Tag::factory()->create();
    $tagId = $tag->id;
    $tag->delete();
    $tag->restore();

    Http::assertSent(function ($request) use ($tagId) {
        return $request['event'] === 'tag.updated'
            && $request['data']['id'] === $tagId;
    });
});
