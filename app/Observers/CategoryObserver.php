<?php

namespace App\Observers;

use App\Jobs\SendWebhookJob;
use App\Models\Category;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->dispatchUpdated($category);
    }

    public function updated(Category $category): void
    {
        $this->dispatchUpdated($category);
    }

    public function restored(Category $category): void
    {
        $this->dispatchUpdated($category);
    }

    public function deleted(Category $category): void
    {
        SendWebhookJob::dispatch('category.deleted', [
            'id' => $category->id,
        ]);
    }

    private function dispatchUpdated(Category $category): void
    {
        SendWebhookJob::dispatch('category.updated', [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }
}
