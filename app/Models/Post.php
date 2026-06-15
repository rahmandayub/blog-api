<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
        static::saving(function (Post $post) {
            if ($post->isDirty('featured_image') && $post->featured_image) {
                $post->convertFeaturedImageToWebp();
            }
        });

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

        try {
            $manager = new ImageManager(new Driver);
            $fileContents = Storage::disk('public')->get($path);
            $image = $manager->decode($fileContents);
            $encoded = $image->encodeUsingFileExtension('webp', 80);

            $directory = dirname($path);
            $filenameWithoutExt = pathinfo($path, PATHINFO_FILENAME);
            $newPath = ($directory === '.' ? '' : $directory.'/').uniqid().'-'.$filenameWithoutExt.'.webp';

            Storage::disk('public')->put($newPath, (string) $encoded);

            // Delete the original file
            Storage::disk('public')->delete($path);

            $this->featured_image = $newPath;
        } catch (\Exception $e) {
            // Log the error but don't block the save
            \Log::error('Failed to convert featured image to WebP: '.$e->getMessage());
        }
    }
}
