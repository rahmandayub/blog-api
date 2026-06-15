<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class OptimizeImagesToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:optimize-webp {--disk=public : The disk where images are stored}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing uploaded images (Posts and Users) to WebP format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = $this->option('disk');
        $manager = new ImageManager(new Driver());

        $this->info("Starting image optimization to WebP on disk: {$disk}");

        // Optimize Post featured_image
        $posts = Post::whereNotNull('featured_image')->get();
        if ($posts->isNotEmpty()) {
            $this->info("Found {$posts->count()} posts with featured images.");
            $bar = $this->output->createProgressBar(count($posts));
            $bar->start();

            foreach ($posts as $post) {
                $path = $post->featured_image;
                $newPath = $this->convertToWebp($manager, $disk, $path);
                
                if ($newPath && $newPath !== $path) {
                    $post->featured_image = $newPath;
                    $post->save(); 
                    // Note: Post model's booted() event will automatically delete the old image!
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // Optimize User profile_photo
        $users = User::whereNotNull('profile_photo')->get();
        if ($users->isNotEmpty()) {
            $this->info("Found {$users->count()} users with profile photos.");
            $barUser = $this->output->createProgressBar(count($users));
            $barUser->start();

            foreach ($users as $user) {
                $path = $user->profile_photo;
                $newPath = $this->convertToWebp($manager, $disk, $path);
                
                if ($newPath && $newPath !== $path) {
                    $user->profile_photo = $newPath;
                    $user->save();
                    // User model doesn't have the automatic deletion event, so we delete manually
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }
                }
                $barUser->advance();
            }
            $barUser->finish();
            $this->newLine();
        }

        $this->info('Image optimization complete! SEO anda pasti jadi lebih baik.');
    }

    /**
     * Convert an image to WebP format and save it.
     */
    private function convertToWebp(ImageManager $manager, string $disk, string $path): ?string
    {
        // Skip if it's already a webp image
        if (Str::endsWith(strtolower($path), '.webp')) {
            return $path;
        }

        if (!Storage::disk($disk)->exists($path)) {
            return null; // File missing
        }

        try {
            $fileContents = Storage::disk($disk)->get($path);
            $image = $manager->read($fileContents);
            
            // Encode to webp format with 80% quality
            $encoded = $image->toWebp(80);

            $directory = dirname($path);
            $filenameWithoutExt = pathinfo($path, PATHINFO_FILENAME);
            
            // Generate a safe unique name for the new WebP file
            $newPath = ($directory === '.' ? '' : $directory . '/') . uniqid() . '-' . $filenameWithoutExt . '.webp';

            Storage::disk($disk)->put($newPath, (string) $encoded);

            return $newPath;
        } catch (\Exception $e) {
            $this->error("\nFailed to convert {$path}: " . $e->getMessage());
            return null;
        }
    }
}
