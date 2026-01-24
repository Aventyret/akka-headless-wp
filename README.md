# Akka Headless WP

This plugin enables running headless Wordpress will the full power
of the Wordpress block editor.

## Installation

Add this repository to you composer.json:

```

  "repositories": [
    ...
    {
      "type": "vcs",
      "url": "https://github.com/Aventyret/akka-headless-wp.git"
    }
```

Install the plugin:

```
composer require aventyret/akka-headless-wp
```

Activate the plugin in your wp admin.

These environment variables are used by the plugin:

```
AKKA_CMS_COOKIE_NAME
AKKA_CMS_COOKIE_PATH
AKKA_CMS_MEDIA_BUCKET_HOSTNAME
AKKA_CMS_MEDIA_BUCKET_PORT
AKKA_CMS_MEDIA_BUCKET_PROTOCOL
AKKA_CMS_URL_INTERNAL
AKKA_FRONTEND_FLUSH_CACHE_KEY
AKKA_FRONTEND_URL
AKKA_FRONTEND_URL_INTERNAL
```

## Theme

Use the [Akka Bas theme](https://github.com/Aventyret/akka-bas-theme) as a boilerplate for your projects theme.

## v2 Migration guide

The api base has changed from `/headless/v1` to `/akka/v2`. Updating to latest `akka-modules` should take care of this.

### Migrate function calls

All Akka classes are renamed and placed in namespace `Akka`. These public functions are changed (note that there are breaking changes in both class names and function names):

```
// v1
\Akka_headless_wp_content::get_akka_post(1);

// v2
\Akka\Post::get_single(1);

// v1
\Akka_headless_wp_content::get_akka_posts($query_args);

// v2
\Akka\Post::get_blurbs($query_args);

// v1
\Akka_headless_wp_content::parse_posts($posts);

// v2
\Akka\Post::posts_to_blurbs($posts);

// v1
\Akka_headless_wp_content::get_post_in_archive($post);

// v2
\Akka\Post::post_to_blurb($post);

```

The following functions are new in v2:

```
\Akka\Post::get_blurb($post_id);

\Akka\PostTypes::unregister_post_post_type();

\Akka\PostTypes::rename_post_type('post', [
  'plural' => __('Articles', 'akka-theme'),
  'singular' => __('Article', 'akka-theme'),
]);

\Akka\Taxonomies::register_taxonomy_for_post_type('category', 'product');

\Akka\Taxonomies::unregister_taxonomy_for_post_type('category', 'post');
```

### Migrate hooks

All Akka hooks are renamed with prefix `akka_` replacing `ahw_`. In addition to this change, the following filters are changed as well:

```
// v1
add_filter('ahw_taxonomy_term_data', function($taxonomy_term_data, $archive_taxonomy_term) {
  return $taxonomy_term_data;
}, 10, 2);
// v2
add_filter('akka_taxonomy_term_archive', function($taxonomy_term_archive, $archive_taxonomy_term) {
  return $taxonomy_term_archive;
}, 10, 2);

// v1
add_filter('ahw_post_type_data', function($post_type_data) {
  return $post_type_data;
}, 10, 2);
// v2
add_filter('akka_post_type_archive', function($post_type_archive) {
  return $post_type_archive;
});

// v1
add_filter('ahw_post_data', function($post_data) {
  return $post_data;
});
// v2 (note that an optional second argument is added)
add_filter('akka_post_single', function($akka_post, $post) {
  return $akka_post;
}, 10, 2);

// v1
add_filter('awh_post_in_archive', function($post_in_archive, $post) {
  return $post_in_archive;
}, 10, 2);
// v2
add_filter('akka_post_blurb', function($post_blurb, $post) {
  return $post_blurb;
}, 10, 2);

// v1
add_filter('ahw_search_result_data', function($search_result_data) {
  return $search_result_data;
});
// v2
add_filter('akka_search_result', function($search_result) {
  return $search_result;
});

// v1
add_filter('ahw_seo_meta', function($seo_meta, $post, $specific_seo_image_is_defined) {
  return $seo_meta;
}, 10, 3);
// v2
add_filter('akka_post_seo_meta', function($seo_meta, $post, $specific_seo_image_is_defined) {
  return $seo_meta;
}, 10, 3);

```

The following filters are new in v2:

```

add_filter('akka_post_{$post_type}_single', function($post_single, $post) {
  return $post_single;
}, 10, 2);

add_filter('akka_post_template_{$page_template}_single', function($post_single, $post) {
  return $post_single;
}, 10, 2);

add_filter('akka_post_{$post_type}_blurb', function($post_blurb, $post) {
  return $post_blurb;
}, 10, 2);

add_filter('akka_post_type_{$post_type}_archive', function($post_type_archive) {
  return $post_type_archive;
});

add_filter('akka_taxonomy_term_{$taxonomy_slug}_archive', function($taxonomy_term_archive, $archive_taxonomy_term) {
  return $taxonomy_term_archive;
}, 10, 2);

add_filter('akka_post_term', function($term, $taxonomy_slug) {
  return $term;
}, 10, 2);
```

### Custom post filter no longer needed

The filter `akka_custom_post_strucure_post_types` is no longer needed for public post types (this filter is implemented in the plugin for these post types).

### Plugin now ships with ACF_Field_Unique_ID

Remove `philipnewcomer/acf-unique-id-field` as a dependency for your akka project (if you have it) since the plugin now ships with it:

```
composer remove philipnewcomer/acf-unique-id-field
```

Also remove the following row if you have it in your themes `ThemeSetup.php` file.

```
\PhilipNewcomer\ACF_Unique_ID_Field\ACF_Field_Unique_ID::init();
```

### Plugin no longer ships with simplehtmldom

If you need it: include it in your theme.
