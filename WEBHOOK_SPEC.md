# Webhook Spec: Blog API → Kalitera

## Overview

When the external blog API (`blog.kalitera.id`) has new or updated content, it sends an HTTP POST request to `kalitera.id` so the local database can be updated in real-time, bypassing the sync command's scheduled interval.

## Endpoint

```
POST https://kalitera.id/api/webhook/blog
```

## Authentication

The blog API must send a shared secret token in the `Authorization` header:

```
Authorization: Bearer {BLOG_WEBHOOK_SECRET}
```

Configuration on kalitera side:

```env
BLOG_WEBHOOK_SECRET=your-shared-secret-here
```

## Payload Format

### Post Created/Updated

```json
{
  "event": "post.updated",
  "data": {
    "id": 1,
    "title": "Judul Artikel",
    "slug": "judul-artikel",
    "content": "<p>Full HTML content</p>",
    "excerpt": "Short excerpt...",
    "featured_image": "uploads/image.webp",
    "created_at": "2026-07-11T09:00:00Z",
    "updated_at": "2026-07-11T10:00:00Z",
    "user": {
      "id": 1,
      "name": "Author Name",
      "email": "author@example.com",
      "profile_photo": "uploads/photo.jpg",
      "bio": "Author biography",
      "role": "Financial Content Writer"
    },
    "category": {
      "id": 1,
      "name": "Akuntansi"
    },
    "tags": [
      {"id": 1, "name": "akuntansi-dasar"},
      {"id": 2, "name": "laporan-keuangan"}
    ]
  }
}
```

### Post Deleted

```json
{
  "event": "post.deleted",
  "data": {
    "id": 1
  }
}
```

### Category Created/Updated

```json
{
  "event": "category.updated",
  "data": {
    "id": 1,
    "name": "Akuntansi",
    "slug": "akuntansi"
  }
}
```

### Category Deleted

```json
{
  "event": "category.deleted",
  "data": {
    "id": 1
  }
}
```

### Tag Created/Updated

```json
{
  "event": "tag.updated",
  "data": {
    "id": 1,
    "name": "akuntansi-dasar",
    "slug": "akuntansi-dasar"
  }
}
```

### Tag Deleted

```json
{
  "event": "tag.deleted",
  "data": {
    "id": 1
  }
}
```

## Event Types

| Event | Description |
|-------|-------------|
| `post.updated` | Post created or updated |
| `post.deleted` | Post deleted |
| `category.updated` | Category created or updated |
| `category.deleted` | Category deleted |
| `tag.updated` | Tag created or updated |
| `tag.deleted` | Tag deleted |
| `post.bulk` | Batch of posts (max 50) for initial sync |
| `ping` | Health check, no action needed |

## Response Codes

| Code | Meaning |
|------|---------|
| 200 | Webhook received and processed |
| 401 | Invalid or missing `Authorization` token |
| 422 | Invalid payload format |
| 500 | Server error |

## Idempotency

The kalitera side uses `external_id` as the unique identifier for upserts. Sending the same webhook multiple times is safe — posts/categories/tags are upserted (insert or update) based on `external_id`.

## Throttling

Maximum 60 requests per minute per IP.

## Implementation Notes (for kalitera side)

- Route: `POST /api/webhook/blog` → `WebhookController@handle`
- Verify Bearer token against `config('services.blog_webhook.secret')`
- Validate payload structure
- Dispatch `Jobs\ProcessBlogWebhook` to queue for async processing
- Return `200` immediately after queueing
