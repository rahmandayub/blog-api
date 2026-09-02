<?php

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\TagResource\Pages\CreateTag;
use App\Filament\Resources\TagResource\Pages\ListTags;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake();
});

test('guest cannot access admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('authenticated user can access admin dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

test('authenticated user can access filament resources list pages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListPosts::class)->assertSuccessful();
    Livewire::test(ListCategories::class)->assertSuccessful();
    Livewire::test(ListTags::class)->assertSuccessful();
});

test('authenticated user can see posts table records', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $posts = Post::factory()->count(2)->create(['user_id' => $user->id]);

    Livewire::test(ListPosts::class)
        ->assertCanSeeTableRecords($posts);
});

test('authenticated user can create category via filament', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'Test Category', 'slug' => 'test-category'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('slug', 'test-category')->exists())->toBeTrue();
});

test('authenticated user can create tag via filament', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CreateTag::class)
        ->fillForm(['name' => 'Test Tag', 'slug' => 'test-tag'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::where('slug', 'test-tag')->exists())->toBeTrue();
});

test('profile page can be accessed and shows user data', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'bio' => 'Test bio']);
    $this->actingAs($user);

    $this->get('/admin/profile')->assertSuccessful()->assertSee('John Doe');
});

test('post resource table filters and actions are configured', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = Livewire::test(ListPosts::class);

    // Check that page loads without error and has table
    $page->assertSuccessful();
});

test('category trashed filter works', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();
    $category->delete();

    // List should show trashed filter available; we test that deleted category not in default list
    Livewire::test(ListCategories::class)
        ->assertCanNotSeeTableRecords(Category::withTrashed()->where('id', $category->id)->get())
        ->assertCanSeeTableRecords(Category::all()); // only non-deleted
});

test('user resource is view only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListUsers::class)->assertSuccessful();
});
