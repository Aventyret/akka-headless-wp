# Classes

All plugin classes are in the `Akka` namespace.

This is a specification of classes and methods available to theme developers.

---

## Post

Handles post data retrieval and transformation.

### get_single

```php
\Akka\Post::get_single($post_id_or_post = null, $post_status = ['publish'], $get_autosaved = false)
```

Returns a full "Post Single" object for a given post.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$post_id_or_post` | `int\|WP_Post\|null` | `null` | Post ID or WP_Post object. If null, uses global `$post`. |
| `$post_status` | `array` | `['publish']` | Allowed post statuses. |
| `$get_autosaved` | `bool` | `false` | If true, fetches autosaved content. |

**Returns:** `array|null` — Post Single object or null if not found.

**Post Single Object Structure:**
```php
[
    'post_id' => int,
    'post_date' => string,          // Formatted date
    'post_date_iso' => string,      // ISO 8601 date
    'post_title' => string,
    'post_type' => string,
    'post_password' => string,
    'post_parent_id' => int,
    'post_status' => string,
    'author' => [
        'id' => int,
        'name' => string,
        'url' => string
    ],
    'slug' => string,
    'excerpt' => string|null,
    'page_template' => string,
    'featured_image' => array|null,  // Image attributes
    'thumbnail_caption' => string,
    'permalink' => string,
    'url' => string,
    'taxonomy_terms' => array,
    'post_content' => string,        // Rendered HTML
    'seo_meta' => array,
]
```

---

### get_blurb

```php
\Akka\Post::get_blurb($post_id)
```

Returns a "Post Blurb" object for a single post by ID.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_id` | `int` | The post ID. |

**Returns:** `array|null` — Post Blurb object or null.

---

### get_blurbs

```php
\Akka\Post::get_blurbs($query_args)
```

Returns an array of Post Blurb objects based on WP_Query arguments.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `array` | Standard WP_Query arguments. |

**Returns:** `array` — Array of Post Blurb objects.

---

### posts_to_blurbs

```php
\Akka\Post::posts_to_blurbs($posts)
```

Converts an array of WP_Post objects to Post Blurb objects.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$posts` | `array` | Array of WP_Post objects. |

**Returns:** `array` — Array of Post Blurb objects.

---

### post_to_blurb

```php
\Akka\Post::post_to_blurb($post)
```

Converts a single WP_Post object to a Post Blurb object.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post` | `WP_Post` | A WordPress post object. |

**Returns:** `array` — Post Blurb object.

**Post Blurb Object Structure:**
```php
[
    'post_id' => int,
    'post_guid' => string,
    'post_date' => string,
    'post_date_iso' => string,
    'url' => string,
    'featured_image' => array|null,
    'post_title' => string,
    'post_type' => string,
    'slug' => string,
    'description' => string,         // Excerpt
    'taxonomy_terms' => array,
]
```

---

### get_url

```php
\Akka\Post::get_url($post_id)
```

Returns the frontend URL for a post.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_id` | `int` | The post ID. |

**Returns:** `string` — The post URL.

---

### get_seo_meta

```php
\Akka\Post::get_seo_meta($post, $post_thumbnail_id = null)
```

Returns SEO metadata for a post. Integrates with Yoast, SEO Framework, and All in One SEO.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post` | `WP_Post` | The post object. |
| `$post_thumbnail_id` | `int\|null` | Optional thumbnail ID for fallback SEO image. |

**Returns:** `array` — SEO meta object with `seo_title`, `seo_description`, `og_*`, `twitter_*`, `canonical_url`, `schema`, etc.

---

## PostTypes

Utilities for registering and managing custom post types.

### register_post_type

```php
\Akka\PostTypes::register_post_type($post_type_slug, $args, $options = [])
```

Registers a custom post type with Akka conventions.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_type_slug` | `string` | The post type identifier (e.g., `'product'`). |
| `$args` | `array` | Standard WordPress `register_post_type` arguments. |
| `$options` | `array` | Akka-specific options (see below). |

**Args (merged with defaults):**
```php
[
    'label' => string,              // Required
    'has_archive' => false,
    'public' => false,
    'exclude_from_search' => false,
    'show_ui' => true,
    'show_in_nav_menus' => true,
    'menu_icon' => 'dashicons-admin-post',
    'hierarchical' => false,
    'show_in_rest' => true,
    'menu_position' => 10,
]
```

**Options:**
```php
[
    'meta_groups' => [],            // Meta field groups
    'acf_field_groups' => [],       // ACF field group definitions
    'allowed_core_blocks' => [],    // Additional allowed blocks
    'unallowed_core_blocks' => [],  // Blocks to remove
    'blocks_template' => [],        // Default blocks template
]
```

**Example:**
```php
\Akka\PostTypes::register_post_type('product', [
    'label' => __('Products', 'theme'),
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-cart',
], [
    'acf_field_groups' => [
        [
            'key' => 'group_product_fields',
            'title' => 'Product Details',
            'fields' => [
                ['name' => 'price', 'label' => 'Price', 'type' => 'number'],
            ],
        ],
    ],
]);
```

---

### unregister_post_post_type

```php
\Akka\PostTypes::unregister_post_post_type()
```

Unregisters the default WordPress 'post' post type.

---

### rename_post_type

```php
\Akka\PostTypes::rename_post_type($post_type, $labels)
```

Renames an existing post type's labels.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_type` | `string` | The post type to rename (e.g., `'post'`). |
| `$labels` | `array` | Array with `'plural'` and `'singular'` keys. |

**Example:**
```php
\Akka\PostTypes::rename_post_type('post', [
    'plural' => __('Articles', 'theme'),
    'singular' => __('Article', 'theme'),
]);
```

---

### set_post_type_blocks_template

```php
\Akka\PostTypes::set_post_type_blocks_template($post_type_slug, $blocks_template)
```

Sets a default blocks template for a post type.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$post_type_slug` | `string` | The post type. |
| `$blocks_template` | `array` | Gutenberg blocks template array. |

---

## Taxonomies

Utilities for registering and managing taxonomies.

### register_taxonomy

```php
\Akka\Taxonomies::register_taxonomy($taxonomy_slug, $args, $options = [])
```

Registers a custom taxonomy with Akka conventions.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$taxonomy_slug` | `string` | The taxonomy identifier. |
| `$args` | `array` | Standard WordPress `register_taxonomy` arguments. |
| `$options` | `array` | Akka-specific options. |

**Options:**
```php
[
    'post_types' => ['post'],           // Post types to attach taxonomy
    'in_archive_post_types' => [],      // Include terms in blurbs for these post types
    'admin_column_post_types' => [],    // Show taxonomy column in admin
    'admin_filter_post_types' => [],    // Show taxonomy filter in admin
    'has_archive' => false,             // Enable archive pages
    'acf_field_groups' => [],           // ACF fields for terms
]
```

**Example:**
```php
\Akka\Taxonomies::register_taxonomy('product_category', [
    'label' => __('Product Categories', 'theme'),
    'hierarchical' => true,
], [
    'post_types' => ['product'],
    'has_archive' => true,
    'in_archive_post_types' => ['product'],
    'admin_column_post_types' => ['product'],
]);
```

---

### register_taxonomy_for_post_type

```php
\Akka\Taxonomies::register_taxonomy_for_post_type($taxonomy, $post_type)
```

Attaches an existing taxonomy to a post type.

---

### unregister_taxonomy_for_post_type

```php
\Akka\Taxonomies::unregister_taxonomy_for_post_type($taxonomy, $post_type)
```

Detaches a taxonomy from a post type.

---

## Resolvers

Helper methods for resolving ACF fields and related data.

### resolve_field

```php
\Akka\Resolvers::resolve_field($fields_source, $field_name)
```

Resolves a field value from a fields array or post object.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields_source` | `array` | Either an array with `'fields'` key, a direct fields array, or a post data array. |
| `$field_name` | `string` | The field name to retrieve. |

**Returns:** `mixed` — The field value or null.

---

### resolve_boolean_field

```php
\Akka\Resolvers::resolve_boolean_field($fields_source, $field_name)
```

Resolves a field as a boolean.

---

### resolve_link_field

```php
\Akka\Resolvers::resolve_link_field($fields_source, $field_name)
```

Resolves an ACF link field.

**Returns:**
```php
[
    'text' => string,
    'url' => string,
    'target' => string
]
```

---

### resolve_array_field

```php
\Akka\Resolvers::resolve_array_field($fields_source, $field_name)
```

Resolves a field as an array (returns `[]` if empty).

---

### resolve_post_blurb_field

```php
\Akka\Resolvers::resolve_post_blurb_field($fields_source, $field_name)
```

Resolves an ACF post object field to a Post Blurb.

---

### resolve_post_blurbs_field

```php
\Akka\Resolvers::resolve_post_blurbs_field($fields_source, $field_name)
```

Resolves an ACF relationship/post object field to multiple Post Blurbs.

---

### resolve_post_single_field

```php
\Akka\Resolvers::resolve_post_single_field($fields_source, $field_name)
```

Resolves an ACF post object field to a Post Single.

---

### resolve_global_field

```php
\Akka\Resolvers::resolve_global_field($field_name)
```

Resolves a global ACF option field (prefixed with `global_`).

---

### resolve_image_field

```php
\Akka\Resolvers::resolve_image_field($fields_source, $field_name, $size = 'full')
```

Resolves an ACF image field to image attributes.

---

### resolve_wysiwyg_field

```php
\Akka\Resolvers::resolve_wysiwyg_field($fields_source, $field_name)
```

Resolves an ACF WYSIWYG field with URL parsing applied.

---

### resolve_image

```php
\Akka\Resolvers::resolve_image($image_id, $size = 'full', $include_caption = false)
```

Returns image attributes for a given attachment ID.

---

### resolve_audio_or_video

```php
\Akka\Resolvers::resolve_audio_or_video($media_id)
```

Returns audio/video attributes for a media attachment.

---

## Archive

Handles archive and listing queries.

### get_post_type_archive

```php
\Akka\Archive::get_post_type_archive($archive_post_type, $page)
```

Returns a post type archive with pagination.

**Returns:** Array with `post_type`, `slug`, `url`, `name`, `count`, `pages`, `posts`, `next_page`.

---

### get_taxonomy_term_archive

```php
\Akka\Archive::get_taxonomy_term_archive($archive_taxonomy, $archive_taxonomy_term, $page, $year = null)
```

Returns a taxonomy term archive with pagination.

---

### get_post_archive

```php
\Akka\Archive::get_post_archive($post_types, $category, $post_tag, $per_page = -1, $offset = 0, $page = 1)
```

Returns posts filtered by category/tag with pagination.

---

### get_posts_feed

```php
\Akka\Archive::get_posts_feed($post_types, $per_page = 50, $offset = 0, $page = 1)
```

Returns a feed of posts from the last 12 months.

---

## Term

Handles taxonomy term data.

### get_single_terms

```php
\Akka\Term::get_single_terms($post)
```

Returns terms for a Post Single, based on the taxonomy map.

---

### get_blurb_terms

```php
\Akka\Term::get_blurb_terms($post)
```

Returns terms for a Post Blurb, based on the blurb taxonomy map.

---

### get_terms

```php
\Akka\Term::get_terms($taxonomy_slug)
```

Returns all terms for a taxonomy.

---

### get_url

```php
\Akka\Term::get_url($term_id)
```

Returns the frontend URL for a term.

---

## AkkaBlocks

Handles registration of Akka Blocks (custom server-rendered blocks).

### register_block_type

```php
\Akka\AkkaBlocks::register_block_type($block_type, $args = [])
```

Registers an Akka Block that renders props to a frontend component.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$block_type` | `string` | Block type identifier (e.g., `'theme/hero'`). |
| `$args` | `array` | Block configuration. |

**Args:**
```php
[
    'akka_component_name' => string,      // Required: Frontend component name
    'post_types' => array,                // Optional: Restrict to post types
    'block_props_callback' => callable,   // Optional: Transform block attributes to props
]
```

**Example:**
```php
\Akka\AkkaBlocks::register_block_type('theme/hero', [
    'akka_component_name' => 'Hero',
    'block_props_callback' => function($post_id, $block_attributes) {
        return [
            'title' => $block_attributes['title'],
            'image' => \Akka\Resolvers::resolve_image($block_attributes['imageId']),
        ];
    },
]);
```

---

### register_splx_block_type

```php
\Akka\AkkaBlocks::register_splx_block_type($block_type, $args = [])
```

Registers a Solarplexus block as an Akka Block.

**Args:**
```php
[
    'akka_component_name' => string,      // Required
    'post_types' => array,                // Optional
    'block_props_callback' => callable,   // Receives ($post_id, $splx_args)
]
```

---

## Blocks

Utilities for managing Gutenberg blocks.

### add_allowed_blocks

```php
\Akka\Blocks::add_allowed_blocks($blocks, $allowed_blocks)
```

Adds blocks to the allowed blocks list.

---

### remove_unallowed_blocks

```php
\Akka\Blocks::remove_unallowed_blocks($blocks, $unallowed_blocks)
```

Removes blocks from the allowed blocks list.

---

### register_core_block_style

```php
\Akka\Blocks::register_core_block_style($block, $style)
```

Registers a custom style for a core block.

---

### register_core_block_variation

```php
\Akka\Blocks::register_core_block_variation($block, $variation)
```

Registers a variation for a core block.

---

### get_h2_blocks

```php
\Akka\Blocks::get_h2_blocks($content, $level = null)
```

Extracts h2 heading text from HTML content (for table of contents).

---

## Utils

Various utility functions.

### parse_url

```php
\Akka\Utils::parse_url($url)
```

Converts WordPress internal URLs to frontend-relative URLs.

---

### is_headless

```php
\Akka\Utils::is_headless()
```

Returns `true` if called during a REST API request (headless context).

---

### flush_frontend_cache

```php
\Akka\Utils::flush_frontend_cache()
```

Triggers a cache flush notification to the frontend application.

---

### get_page_template_slug

```php
\Akka\Utils::get_page_template_slug($post)
```

Returns the page template slug for a post.

---

### wrap_left_and_right_aligned_blocks

```php
\Akka\Utils::wrap_left_and_right_aligned_blocks($html_string, $options = [])
```

Wraps left/right aligned blocks for proper rendering.

---

## Search

Search functionality with Relevanssi integration.

### search

```php
\Akka\Search::search($query, $post_type, $category_slugs = [], $term_slugs = [], $taxonomy = 'post_tag', $offset = 0, $page = 1)
```

Performs a search query.

**Returns:**
```php
[
    'count' => int,
    'pages' => int,
    'posts' => array,  // Post Blurbs
]
```

---

## SiteMeta

Site-wide metadata and navigation.

### get_site_meta

```php
\Akka\SiteMeta::get_site_meta()
```

Returns site metadata including navigation, global fields, and redirects.

---

## Acf

ACF field group registration utilities.

### register_field_group

```php
\Akka\Acf::register_field_group($field_group)
```

Registers an ACF field group programmatically.

**Required keys:** `key`, `title`, `fields`, `location`.

---

## MetaFields

Native WordPress meta field registration.

### register_post_meta_field

```php
\Akka\MetaFields::register_post_meta_field($meta_group, $meta_fields, $options = [])
```

Registers a group of post meta fields with editor UI support.

---

Back to the [docs](../../README.md).