<?php

namespace App\Observers;

use App\Jobs\SendWebhookJob;
use App\Models\Post;
use Illuminate\Support\Str;

class PostObserver
{
    public function created(Post $post): void
    {
        $this->dispatchUpdated($post);
    }

    public function updated(Post $post): void
    {
        $this->dispatchUpdated($post);
    }

    public function restored(Post $post): void
    {
        $this->dispatchUpdated($post);
    }

    public function deleted(Post $post): void
    {
        SendWebhookJob::dispatch('post.deleted', [
            'id' => $post->id,
        ]);
    }

    private function dispatchUpdated(Post $post): void
    {
        $post->load(['category', 'tags', 'user']);

        SendWebhookJob::dispatch('post.updated', [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'excerpt' => Str::limit(strip_tags($post->content), 200),
            'featured_image' => $post->featured_image,
            'status' => $post->status,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'user' => $post->user ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'email' => $post->user->email,
                'profile_photo' => $post->user->profile_photo,
                'bio' => $post->user->bio,
            ] : null,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
            ] : null,
            'tags' => $post->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->toArray(),
        ]);
    }
}
