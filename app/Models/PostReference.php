<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReference extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'title', 'url', 'source', 'sort_order'];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
