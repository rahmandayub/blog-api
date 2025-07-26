<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "slug" => $this->slug,
            "content" => $this->content,
            "featured_image" => $this->featured_image
                ? (preg_match("/^https?:\/\//", $this->featured_image)
                    ? $this->featured_image
                    : "uploads/" . ltrim($this->featured_image, "/"))
                : null,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "category" => $this->category
                ? [
                    "id" => $this->category->id,
                    "name" => $this->category->name,
                ]
                : null,
            "tags" => $this->tags->map(function ($tag) {
                return [
                    "id" => $tag->id,
                    "name" => $tag->name,
                ];
            }),
            "user" => $this->user
                ? [
                    "id" => $this->user->id,
                    "name" => $this->user->name,
                    "bio" => $this->user->bio,
                    "profile_photo" => $this->user->profile_photo
                        ? (preg_match(
                            "/^https?:\/\//",
                            $this->user->profile_photo,
                        )
                            ? $this->user->profile_photo
                            : "uploads/" .
                                ltrim($this->user->profile_photo, "/"))
                        : null,
                    "email" => $this->user->email,
                ]
                : null,
        ];
    }
}
