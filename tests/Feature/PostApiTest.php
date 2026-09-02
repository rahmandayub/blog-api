<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('blog.api_key', 'test-api-key-value');
    Http::fake();
});

function apiHeaders(): array
{
    return [
        'Authorization' => 'Bearer test-api-key-value',
        'Accept' => 'application/json',
    ];
}

test('posts index returns only published posts', function () {
    $published = Post::factory()->create(['status' => 'publish']);
    $draft = Post::factory()->create(['status' => 'draft']);

    $response = $this->getJson('/api/posts', apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids)->toContain($published->id)
        ->not->toContain($draft->id);
});

test('posts index has pagination structure with default 15 per page', function () {
    Post::factory()->count(20)->create(['status' => 'publish']);

    $response = $this->getJson('/api/posts', apiHeaders())->assertSuccessful();

    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'title', 'slug', 'content', 'featured_image', 'status', 'published_at', 'created_at', 'updated_at', 'category', 'tags', 'user'],
        ],
        'links' => ['first', 'last', 'prev', 'next'],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);

    expect($response->json('meta.per_page'))->toBe(15)
        ->and($response->json('data'))->toHaveCount(15);
});

test('posts index per_page capped at 50', function () {
    Post::factory()->count(60)->create(['status' => 'publish']);

    $response = $this->getJson('/api/posts?per_page=100', apiHeaders())->assertSuccessful();

    expect($response->json('meta.per_page'))->toBe(50)
        ->and($response->json('data'))->toHaveCount(50);
});

test('posts index per_page respects custom value', function () {
    Post::factory()->count(10)->create(['status' => 'publish']);

    $response = $this->getJson('/api/posts?per_page=5', apiHeaders())->assertSuccessful();

    expect($response->json('meta.per_page'))->toBe(5)
        ->and($response->json('data'))->toHaveCount(5);
});

test('posts index search filters by title', function () {
    $matched = Post::factory()->create(['title' => 'Laravel Unique Searchable Title XYZ', 'status' => 'publish']);
    $unmatched = Post::factory()->create(['title' => 'Unrelated Title ABC', 'status' => 'publish']);

    $response = $this->getJson('/api/posts?search=XYZ', apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids)->toContain($matched->id)
        ->not->toContain($unmatched->id);
});

test('posts index search filters by content', function () {
    $matched = Post::factory()->create(['content' => 'This content contains UNIQUECONTENT12345 word', 'status' => 'publish']);
    $unmatched = Post::factory()->create(['content' => 'Nothing relevant here', 'status' => 'publish']);

    $response = $this->getJson('/api/posts?search=UNIQUECONTENT12345', apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids)->toContain($matched->id)
        ->not->toContain($unmatched->id);
});

test('posts index category_id filter works', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $postInCat1 = Post::factory()->create(['category_id' => $category1->id, 'status' => 'publish']);
    $postInCat2 = Post::factory()->create(['category_id' => $category2->id, 'status' => 'publish']);

    $response = $this->getJson('/api/posts?category_id='.$category1->id, apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids)->toContain($postInCat1->id)
        ->not->toContain($postInCat2->id);
});

test('posts index sort_by_date desc orders newest first', function () {
    $old = Post::factory()->create(['status' => 'publish', 'created_at' => now()->subDays(5)]);
    $new = Post::factory()->create(['status' => 'publish', 'created_at' => now()]);

    $response = $this->getJson('/api/posts?sort_by_date=desc', apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids[0])->toBe($new->id)
        ->and($ids[1])->toBe($old->id);
});

test('posts index sort_by_date asc orders oldest first', function () {
    $old = Post::factory()->create(['status' => 'publish', 'created_at' => now()->subDays(5)]);
    $new = Post::factory()->create(['status' => 'publish', 'created_at' => now()]);

    $response = $this->getJson('/api/posts?sort_by_date=asc', apiHeaders())->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids[0])->toBe($old->id)
        ->and($ids[1])->toBe($new->id);
});

test('posts index sort_by_date invalid value is ignored', function () {
    Post::factory()->count(3)->create(['status' => 'publish']);

    $this->getJson('/api/posts?sort_by_date=invalid', apiHeaders())->assertSuccessful();
});

test('posts show returns published post with correct resource structure', function () {
    $post = Post::factory()->create(['status' => 'publish']);
    $post->tags()->attach(Tag::factory()->create());

    $response = $this->getJson('/api/posts/'.$post->slug, apiHeaders())->assertSuccessful();

    $response->assertJsonStructure([
        'data' => ['id', 'title', 'slug', 'content', 'featured_image', 'status', 'published_at', 'created_at', 'updated_at', 'category', 'tags', 'user'],
    ]);

    $data = $response->json('data');
    expect($data['id'])->toBe($post->id)
        ->and($data['slug'])->toBe($post->slug)
        ->and($data['title'])->toBe($post->title)
        ->and($data['status'])->toBe('publish')
        ->and($data['category']['id'])->toBe($post->category->id)
        ->and($data['category']['name'])->toBe($post->category->name)
        ->and($data['user']['id'])->toBe($post->user->id)
        ->and($data['user']['name'])->toBe($post->user->name)
        ->and($data['user'])->toHaveKeys(['id', 'name', 'bio', 'profile_photo', 'email'])
        ->and($data['tags'])->toBeArray()
        ->and($data['tags'][0]['id'])->toBeInt();
});

test('posts show returns 404 for draft post with valid slug', function () {
    $draft = Post::factory()->draft()->create();

    $this->getJson('/api/posts/'.$draft->slug, apiHeaders())->assertNotFound();
});

test('posts show featured_image handling null and url prefix', function () {
    $postWithoutImage = Post::factory()->create(['status' => 'publish', 'featured_image' => null]);
    $response = $this->getJson('/api/posts/'.$postWithoutImage->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.featured_image'))->toBeNull();

    $postWithRelativePath = Post::factory()->create(['status' => 'publish', 'featured_image' => 'featured-images/image.jpg']);
    $response = $this->getJson('/api/posts/'.$postWithRelativePath->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.featured_image'))->toBe('uploads/featured-images/image.jpg');

    $postWithAbsoluteUrl = Post::factory()->create(['status' => 'publish', 'featured_image' => 'https://example.com/image.jpg']);
    $response = $this->getJson('/api/posts/'.$postWithAbsoluteUrl->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.featured_image'))->toBe('https://example.com/image.jpg');
});

test('categories index returns all categories', function () {
    $categories = Category::factory()->count(3)->create();

    $response = $this->getJson('/api/categories', apiHeaders())->assertSuccessful();

    $response->assertJsonCount(3);
    $response->assertJsonStructure([
        '*' => ['id', 'name', 'slug', 'created_at', 'updated_at'],
    ]);
});

test('categories index requires authentication', function () {
    $this->getJson('/api/categories')->assertUnauthorized()->assertJson(['message' => 'Missing API key']);
    $this->getJson('/api/categories', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
});

test('posts index requires authentication', function () {
    $this->getJson('/api/posts')->assertUnauthorized();
    $this->getJson('/api/posts/'.$some = 'any', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
});

test('posts pagination respects page parameter', function () {
    Post::factory()->count(20)->create(['status' => 'publish']);

    $page1 = $this->getJson('/api/posts?per_page=10&page=1', apiHeaders())->assertSuccessful();
    $page2 = $this->getJson('/api/posts?per_page=10&page=2', apiHeaders())->assertSuccessful();

    $idsPage1 = collect($page1->json('data'))->pluck('id')->toArray();
    $idsPage2 = collect($page2->json('data'))->pluck('id')->toArray();

    expect(array_intersect($idsPage1, $idsPage2))->toBeEmpty();
    expect($page1->json('meta.current_page'))->toBe(1)
        ->and($page2->json('meta.current_page'))->toBe(2);
});

test('posts index returns user profile_photo handling', function () {
    $userWithPhoto = User::factory()->create(['profile_photo' => 'profile-photos/photo.jpg']);
    $post = Post::factory()->create(['status' => 'publish', 'user_id' => $userWithPhoto->id]);

    $response = $this->getJson('/api/posts/'.$post->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.user.profile_photo'))->toBe('uploads/profile-photos/photo.jpg');

    $userWithAbsolute = User::factory()->create(['profile_photo' => 'https://cdn.example.com/photo.jpg']);
    $post2 = Post::factory()->create(['status' => 'publish', 'user_id' => $userWithAbsolute->id]);
    $response = $this->getJson('/api/posts/'.$post2->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.user.profile_photo'))->toBe('https://cdn.example.com/photo.jpg');

    $userNull = User::factory()->create(['profile_photo' => null, 'bio' => null]);
    $post3 = Post::factory()->create(['status' => 'publish', 'user_id' => $userNull->id]);
    $response = $this->getJson('/api/posts/'.$post3->slug, apiHeaders())->assertSuccessful();
    expect($response->json('data.user.profile_photo'))->toBeNull()
        ->and($response->json('data.user.bio'))->toBeNull();
});
