# API Endpoints

The plugin exposes REST API endpoints under the base path `/wp-json/akka/v2/`.

---

## Site Meta

### GET /akka/v2/site_meta

Returns site-wide metadata including navigation, global ACF fields, and redirects.

The response of this endpoint can be adjusted with the filters `akka_site_meta` and `akka_site_meta_menu_id`.

`redirects` is currently only included if Yoast Premium is active.

**Response:**
```json
{
  "navigation": {
    "primary": [
      {
        "id": 123,
        "parent_id": null,
        "url": "/about",
        "title": "About Us",
        "description": "",
        "children": []
      }
    ],
    "footer": [...]
  },
  "navigation_meta": {
    "primary": { "name": "Primary Menu" }
  },
  "header": {
    "home_url": "/",
    "posts_url": "/blog"
  },
  "cookies": {...},
  "redirects": {
    "plain": [
      { "origin": "old-page", "target": "/new-page", "status_code": 301 }
    ],
    "regex": [...]
  }
}
```

---

## Post by Permalink

### GET /akka/v2/post/{permalink}

Returns a Post Single, Taxonomy term archive or Post type archive by its permalink path.

The response of this endpoint can be adjusted with the filters `akka_post_single`, `akka_taxonomy_term_archive` and `akka_post_type_archive` depending on the type of content.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `permalink` | `string` | The URL path, URL encoded without leading slash (e.g., `blog%2Fmy-article`). |

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `cms_signed_in` | `string` | Authentication cookie for preview. |
| `autosave` | `bool` | If `true`, returns autosaved content. |
| `page` | `int` | Page number for archive pages. |

**Response:** See [Post Single Object](classes/README.md#get_single)

**Example:**
```
GET /wp-json/akka/v2/post/%2F
GET /wp-json/akka/v2/post/about
GET /wp-json/akka/v2/post/blog%2F2024%2Fmy-article
```

---

### GET /akka/v2/post/

Alternative endpoint using query parameter. This endpoint is preferred in environments where URL encoding is not consitant, like on Azure.

Returns a Post Single, Taxonomy term archive or Post type archive by its permalink path.

The response of this endpoint can be adjusted with the filters `akka_post_single`, `akka_taxonomy_term_archive` and `akka_post_type_archive` depending on the type of content.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `permalink` | `string` | Yes | The URL path. |

**Example:**
```
GET /wp-json/akka/v2/post/?permalink=/
GET /wp-json/akka/v2/post/?permalink=about
```

---

## Post by ID

### GET /akka/v2/post_by_id/{post_id}

Returns a Post Single by its WordPress post ID.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `post_id` | `int` | The WordPress post ID. |

**Example:**
```
GET /wp-json/akka/v2/post_by_id/123
```

---

## Attachment by ID

### GET /akka/v2/attachment_by_id/{attachment_id}

Returns image attributes for an attachment.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `attachment_id` | `int` | The attachment ID. |

**Response:**
```json
{
  "id": 456,
  "src": "https://example.com/wp-content/uploads/image.jpg",
  "width": 1200,
  "height": 800,
  "alt": "Image description"
}
```

---

## Posts Archive

### GET /akka/v2/posts

Returns a paginated list of Post Blurbs.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `post_type` | `string\|array` | `['post']` | Post type(s) to query. |
| `category` | `string` | | Filter by category slug. |
| `post_tag` | `string` | | Filter by tag slug. |
| `per_page` | `int` | `-1` | Posts per page (`-1` for all). |
| `offset` | `int` | `0` | Number of posts to skip. |
| `page` | `int` | `1` | Page number. |

**Response:**
```json
{
  "count": 42,
  "pages": 5,
  "posts": [
    {
      "post_id": 123,
      "post_title": "My Article",
      "url": "/blog/my-article",
      "featured_image": {...},
      "description": "Article excerpt...",
      "post_date": "January 15, 2024",
      "post_date_iso": "2024-01-15T10:00:00+00:00"
    }
  ],
  "next_page": "/blog?page=2"
}
```

---

## Search

### GET /akka/v2/search/{query}

Performs a search query.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `query` | `string` | URL-encoded search term. |

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `post_type` | `string` | Filter by post type. |
| `category` | `string` | Filter by category slug. |
| `tag` | `string` | Filter by tag slug. |
| `taxonomy` | `string` | Taxonomy for tag filter (default: `post_tag`). |
| `page` | `int` | Page number. |
| `offset` | `int` | Number of posts to skip. |

**Response:**
```json
{
  "count": 15,
  "pages": 2,
  "posts": [...]
}
```

### GET /akka/v2/search

Alternative endpoint using query parameter for search term.

---

## Editor Block (Internal)

### POST /akka/v2/editor/block

Renders an Akka Block for the editor preview. **Requires authentication with `edit_posts` capability.**

**Request Body:**
```json
{
  "post_id": 123,
  "block_type": "akka/hero",
  "block_attributes": {
    "title": "Hello World"
  }
}
```

**Response:** Rendered block HTML.

---

## Health Check

### GET /akka/healthz

Simple health check endpoint.

**Response:** `OK`

---

## Response Types

### Post Single Object

The full post representation. See [Post.get_single()](classes/README.md#get_single) for structure.

### Post Blurb Object

The compact post representation for listings. See [Post.post_to_blurb()](classes/README.md#post_to_blurb) for structure.

### Archive Response

Archives return:
```json
{
  "post_type": "post_type|taxonomy_term",
  "slug": "string",
  "url": "/archive-url",
  "post_title": "Archive Title",
  "name": "Archive Name",
  "count": 42,
  "pages": 5,
  "posts": [...],
  "next_page": "/archive-url?page=2",
  "seo_meta": {...}
}
```

---

## Authentication

Most endpoints use the `can_get_content` permission callback. The apis are currently not protected by any authentication.

---

## Error Responses

Status code should be 200 for all successfull responses.

### 400 Bad requests

```json
{"message":"Missing parameter"}
```

### 404 Post Not Found

When a permalink doesn't match any content:
```json
{"message":"Post not found"}
```

---

Back to the [docs](../../README.md).