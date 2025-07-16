<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryApiController extends Controller
{
    // GET /api/categories
    public function index()
    {
        return Category::all();
    }
}
