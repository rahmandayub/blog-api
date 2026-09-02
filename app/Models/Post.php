<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'category_id', 'featured_image', 'user_id', 'status', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

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
        static::saving(function (Post $post) {
            // Manage published_at transition
            if ($post->isDirty('status')) {
                if ($post->status === 'publish' && ! $post->published_at) {
                    $post->published_at = now();
                } elseif ($post->status === 'draft') {
                    $post->published_at = null;
                }
            }

            if ($post->isDirty('featured_image') && $post->featured_image) {
                $post->convertFeaturedImageToWebp();
            }
        });

        static::deleting(function (Post $post) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
        });
    }

    /**
     * Convert the featured image to WebP format if it is not already.
     */
    protected function convertFeaturedImageToWebp(): void
    {
        $path = $this->featured_image;

        if (! $path || Str::endsWith(strtolower($path), '.webp')) {
            return;
        }

        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        $original = $this->getOriginal('featured_image');

        try {
            $manager = new ImageManager(new Driver);
            $fileContents = Storage::disk('public')->get($path);
            $image = $manager->decode($fileContents);
            $encoded = $image->encodeUsingFileExtension('webp', 80);

            $directory = dirname($path);
            $filenameWithoutExt = pathinfo($path, PATHINFO_FILENAME);
            $newPath = ($directory === '.' ? '' : $directory.'/').uniqid().'-'.$filenameWithoutExt.'.webp';

            Storage::disk('public')->put($newPath, (string) $encoded);

            $this->featured_image = $newPath;

            Storage::disk('public')->delete($path);

            if ($original && $original !== $path && Storage::disk('public')->exists($original)) {
                Storage::disk('public')->delete($original);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to convert featured image to WebP: '.$e->getMessage());
        }
    }
}
