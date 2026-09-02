<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;

class PostApiController extends Controller
{
    // GET /api/posts
    public function index()
    {
        $query = Post::with(['category', 'tags', 'user'])->where(
            'status',
            'publish',
        );

        if (request()->has('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere(
                    'content',
                    'like',
                    "%{$search}%",
                );
            });
        }

        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }

        // Add sorting by date
        if (request()->has('sort_by_date')) {
            $sortOrder = strtolower(request('sort_by_date'));
            if (in_array($sortOrder, ['asc', 'desc'])) {
                $query->orderBy('created_at', $sortOrder);
            }
        }

        $perPage = min((int) request('per_page', 15), 50);

        return PostResource::collection(
            $query->paginate($perPage),
        );
    }

    // GET /api/posts/{slug}
    public function show($slug)
    {
        $post = Post::with(['category', 'tags', 'user'])
            ->where('slug', $slug)
            ->where('status', 'publish')
            ->firstOrFail();

        return new PostResource($post);
    }
}
