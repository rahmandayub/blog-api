<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'category_id', 'featured_image', 'user_id', 'status'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Post $post) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
        });

        static::updating(function (Post $post) {
            $originalImage = $post->getOriginal('featured_image');
            if ($post->isDirty('featured_image') && $originalImage && Storage::disk('public')->exists($originalImage)) {
                Storage::disk('public')->delete($originalImage);
            }
        });
    }
}
