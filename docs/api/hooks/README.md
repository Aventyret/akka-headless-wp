# Hooks

This document lists all action and filter hooks provided by the Akka plugin for theme developers to extend functionality.

---

## Post Filters

### akka_post_single

Filters the Post Single object before it's returned.

```php
add_filter('akka_post_single', function($akka_post, $post) {
    // Modify the post single data
    $akka_post['custom_field'] = Resolvers::resolve_field($akka_post, 'custom_field');
    return $akka_post;
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$akka_post` | `array` | The Post Single object. |
| `$post` | `WP_Post` | The original WordPress post. |

---

### akka_post_{$post_type}_single

Filters the Post Single for a specific post type. Parameters are the same as for `akka_post_single`.

```php
add_filter('akka_post_product_single', function($akka_post) {
    $akka_post['custom_field'] = Resolvers::resolve_field($akka_post, 'custom_field');
    return $akka_post;
});
```

---

### akka_post_template_{$template}_single

Filters the Post Single for posts with a specific page template. Parameters are the same as for `akka_post_single`.

```php
add_filter('akka_post_template_landing-page_single', function($akka_post) {
    $akka_post['custom_field'] = Resolvers::resolve_field($akka_post, 'custom_field');
    return $akka_post;
});
```

---

### akka_post_blurb

Filters the Post Blurb object before it is included in listings, archives and search results.

```php
add_filter('akka_post_blurb', function($post_blurb) {
    $post_blurb['custom_field'] = Resolvers::resolve_field($post_blurb, 'custom_field');
    return $post_blurb;
});
```

---

### akka_post_{$post_type}_blurb

Filters the Post Blurb for a specific post type.

```php
add_filter('akka_post_product_blurb', function($post_blurb, $post) {
    $post_blurb['price'] = get_field('price', $post->ID);
    return $post_blurb;
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_blurb` | `array` | The Post Blurb object. |
| `$post` | `WP_Post` | The original WordPress post. |

---

### akka_post_seo_meta

Filters the SEO metadata for a post.

```php
add_filter('akka_post_seo_meta', function($seo_meta, $pos, $specific_seo_image_is_defined) {
    if (!$specific_seo_image_is_defined) {
        $seo_meta['seo_image_url'] = '/images/default-og.jpg';
    }
    return $seo_meta;
}, 10, 3);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$seo_meta` | `array` | The Post Seo meta object. |
| `$post` | `WP_Post` | The original WordPress post. |
| `$specific_seo_image_is_defined` | `boolean` | If a specific SEO image is defined. |

---

### akka_seo_description

Filters the SEO description (meta description).

```php
add_filter('akka_seo_description', function($description, $post) {
    return wp_trim_words($description, 30);
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$description` | `string` | The SEO description. |
| `$post` | `WP_Post` | The original WordPress post. |

---

### akka_post_schema

Filters the JSON-LD schema array for a post.

```php
add_filter('akka_post_schema', function($schema, $akka_post) {
    // Add custom schema
    return $schema;
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$schema` | `array` | The Json-LD schema object. |
| `$akka_post` | `array` | The Post Single object. |

---

### akka_post_blurb_image_size

Filters the image size used for featured images in Post Blurbs.

```php
add_filter('akka_post_blurb_image_size', function($size) {
    return 'square';
});
```

**Default:** `'full'`

---

## Archive Filters

### akka_post_type_archive

Filters post type archive responses.

```php
add_filter('akka_post_type_archive', function($post_type_archive) {
    return $post_type_archive;
});
```

---

### akka_post_type_{$post_type}_archive

Filters post type archive responses for a specific post type. Parameters are the same as for `akka_post_type_archive`.

```php
add_filter('akka_post_type_product_archive', function($archive) {
    $archive['featured_products'] = get_featured_products();
    return $archive;
});
```

---

### akka_taxonomy_term_archive

Filters taxonomy term archive responses.

```php
add_filter('akka_taxonomy_term_archive', function($archive, $term) {
    return $archive;
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$archive` | `array` | The Taxonomy Term Archive object. |
| `$term` | `WP_Term` | The original WordPress term. |

---

### akka_taxonomy_term_{$taxonomy_slug}_archive

Filters archive data for a specific taxonomy. Parameters are the same as for `akka_taxonomy_term_archive`.

```php
add_filter('akka_taxonomy_term_post_tag_archive', function($archive, $term) {
    return $archive;
}, 10, 2);
```

---

### akka_{$post_type}_archive_query_args

Filters WP_Query arguments for a post type archive.

```php
add_filter('akka_product_archive_query_args', function($query_args) {
    $query_args['orderby'] = 'meta_value';
    return $query_args;
});
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `WP_Query_Args` | Query args for post type archives. |

---

### akka_get_posts_args

Filters WP_Query arguments for general post queries.

```php
add_filter('akka_get_posts_args', function($query_args) {
    return $query_args;
});
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `WP_Query_Args` | Query args for get posts queries. |

---

## Term Filters

### akka_post_term

Filters individual term data in post term arrays.

```php
add_filter('akka_post_term', function($term, $taxonomy_slug) {
    if ($taxonomy_slug != 'custom_tax') {
        return $term;
    }
    $term['icon'] = get_field('icon', 'term_' . $term['term_id']);
    return $term;
}, 10, 2);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$term` | `array` | Term object. |
| `$taxonomy_slug` | `string` | Taxonomy slug. |

---

### akka_term_url

Filters the URL for a taxonomy term.

```php
add_filter('akka_term_url', function($url, $term, $taxonomy) {
    return $url;
}, 10, 3);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | `string` | Term url. |
| `$term` | `array` | Term object. |
| `$taxonomy` | `string` | Taxonomy slug. |

---

### akka_term_seo_meta

Filters SEO metadata for taxonomy terms.

```php
add_filter('akka_term_seo_meta', function($seo_meta, $term_data, $specific_seo_image, $specific_description) {
    return $seo_meta;
}, 10, 4);
```

---

### akka_primary_term_id_column_key

Filters the array key used for primary term lookup.

**Default:** `'id'`

---

## Routing Filters

### akka_post_pre_redirect_post_id

Filters the post ID before checking for redirects.

```php
add_filter('akka_post_pre_redirect_post_id', function($post_id, $permalink) {
    return $post_id;
}, 10, 2);
```

---

### akka_post_not_found_response

Filters the response when a post is not found. Return a valid post data array to serve fallback content.

```php
add_filter('akka_post_not_found_response', function($permalink) {
    // Return 404 page response
    return \Akka\Post::get_single(get_option('404_page'));
});
```

---

## Site Meta Filters

### akka_site_meta

Filters the complete site meta object.

```php
add_filter('akka_site_meta', function($site_meta) {
    $site_meta['copyright_year'] = date('Y');
    return $site_meta;
});
```

---

### akka_site_meta_menu_id

Filters menu IDs before fetching menu items.

```php
add_filter('akka_site_meta_menu_id', function($menu_id, $menu_slug) {
    return $menu_id;
}, 10, 2);
```

---

## Block Filters

### akka_allowed_blocks

Filters the list of allowed Gutenberg blocks.

```php
add_filter('akka_allowed_blocks', function($blocks) {
    $blocks[] = 'core/table';
    return $blocks;
});
```

---

## Search Filters

### akka_search_query_args

Filters search WP_Query arguments.

```php
add_filter('akka_search_query_args', function($query_args) {
    $query_args['posts_per_page'] = 20;
    return $query_args;
});
```

---

### akka_search_result

Filters search results.

```php
add_filter('akka_search_result', function($result) {
    return $result;
});
```

---

## Utility Filters

### akka_post_parse_url

Filters parsed URLs.

```php
add_filter('akka_post_parse_url', function($url) {
    return $url;
});
```

---

### akka_post_replace_hrefs

Filters content after href replacement.

```php
add_filter('akka_post_replace_hrefs', function($content) {
    return $content;
});
```

---

### akka_img_attributes

Filters image attribute arrays.

```php
add_filter('akka_img_attributes', function($attributes) {
    $attributes['loading'] = 'lazy';
    return $attributes;
});
```

---

### akka_image_caption

Filters image captions.

```php
add_filter('akka_image_caption', function($caption, $image_id) {
    return $caption;
}, 10, 2);
```

---

## Schema Filters

### akka_schema_search_page_url

Provides the search page URL for schema.org SearchAction.

```php
add_filter('akka_schema_search_page_url', function() {
    return '/search?q=';
});
```

---

### akka_schema_organization_contact_point

Provides contact point data for schema.org Organization.

```php
add_filter('akka_schema_organization_contact_point', function() {
    return [
        '@type' => 'ContactPoint',
        'telephone' => '+1-800-555-1234',
        'contactType' => 'customer service',
    ];
});
```

---

## Action Hooks

### akka_pre_post_content

Fires before post content is processed. Useful for setting up context.

```php
add_action('akka_pre_post_content', function($akka_post) {
    // Set up any global state needed for content rendering
});
```

---

### akka_cache_flushed

Fires after the frontend cache flush is triggered.

```php
add_action('akka_cache_flushed', function() {
    // Perform additional cache invalidation
});
```

---

Back to the [docs](../../README.md).