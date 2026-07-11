<?php

namespace App\Observers;

use App\Jobs\SendWebhookJob;
use App\Models\Tag;

class TagObserver
{
    public function created(Tag $tag): void
    {
        $this->dispatchUpdated($tag);
    }

    public function updated(Tag $tag): void
    {
        $this->dispatchUpdated($tag);
    }

    public function restored(Tag $tag): void
    {
        $this->dispatchUpdated($tag);
    }

    public function deleted(Tag $tag): void
    {
        SendWebhookJob::dispatch('tag.deleted', [
            'id' => $tag->id,
        ]);
    }

    private function dispatchUpdated(Tag $tag): void
    {
        SendWebhookJob::dispatch('tag.updated', [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
        ]);
    }
}
