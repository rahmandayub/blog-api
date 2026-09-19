# API Documentation

## Authentication

All endpoints require authentication using Laravel Sanctum.  
Include your API token in the `Authorization` header:

```
Authorization: Bearer {token}
```

---

## Endpoints

### List Posts

**GET /api/posts**

Returns a paginated list of posts.

#### Query Parameters

-   `search` (optional): Filter posts by title or content.
-   `category_id` (optional): Filter posts by category.
-   `per_page` (optional): Number of posts per page (default: 15).
-   `page` (optional): Page number.

**Example:**

```
GET /api/posts?search=laravel&category_id=2&per_page=10&page=2
```

#### Response

```json
{
  "data": [
    {
      "id": 1,
      "title": "Example Post",
      "slug": "example-post",
      "content": "...",
      "featured_image": "...",
      "status": "publish",
      "published_at": "...",
      "created_at": "...",
      "updated_at": "...",
      "category": {
        "id": 2,
        "name": "Tech"
      },
      "tags": [
        { "id": 1, "name": "Laravel" }
      ],
      "references": [
        {
          "id": 10,
          "title": "Laravel Documentation",
          "url": "https://laravel.com/docs",
          "source": "Laravel",
          "sort_order": 0
        }
      ],
      "user": {
        "id": 5,
        "name": "John Doe"
      }
    }
    // ...
  ],
  "links": { ... },
  "meta": { ... }
}
```

> `references` selalu array (kosong `[]` jika artikel tidak punya referensi). Sudah terurut `sort_order` menaik lalu `id` menaik. `source` bisa `null`.

---

### Get Single Post

**GET /api/posts/{slug}**

Returns details for a single post.

#### Example:

```
GET /api/posts/example-post
```

#### Response

```json
{
    "data": {
        "id": 1,
        "title": "Example Post",
        "slug": "example-post",
        "content": "...",
        "featured_image": "...",
        "status": "publish",
        "published_at": "...",
        "created_at": "...",
        "updated_at": "...",
        "category": {
            "id": 2,
            "name": "Tech"
        },
        "tags": [{ "id": 1, "name": "Laravel" }],
        "references": [
            {
                "id": 10,
                "title": "Laravel Documentation",
                "url": "https://laravel.com/docs",
                "source": "Laravel",
                "sort_order": 0
            },
            {
                "id": 11,
                "title": "PHP Manual",
                "url": "https://www.php.net/manual",
                "source": null,
                "sort_order": 1
            }
        ],
        "user": {
            "id": 5,
            "name": "John Doe"
        }
    }
}
```

#### Field `references`

| Field | Type | Wajib | Keterangan |
|-------|------|-------|------------|
| `id` | integer | ya | ID referensi |
| `title` | string | ya | Judul referensi |
| `url` | string | ya | URL lengkap `https://...`, buka di tab baru |
| `source` | string \| null | tidak | Nama penerbit/situs, bisa `null` — sembunyikan jika `null` |
| `sort_order` | integer | ya | Urutan tampil, kecil → tampil duluan. Jangan sort ulang di frontend |

---

### List Categories

**GET /api/categories**

Returns all categories.

#### Example:

```
GET /api/categories
```

#### Response

```json
[
    { "id": 1, "name": "Tech" },
    { "id": 2, "name": "News" }
]
```

---

## Notes

-   All endpoints are read-only.
-   All requests must be authenticated.
-   Pagination metadata is included in list responses.
