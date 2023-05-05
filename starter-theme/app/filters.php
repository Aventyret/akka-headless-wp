<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter("excerpt_more", function () {
    return sprintf(
        ' &hellip; <a href="%s">%s</a>',
        get_permalink(),
        __("Continued", "sage")
    );
});

/**
 * Allow svg uploads.
 *
 * @return array
 */
add_filter("upload_mimes", function ($mimes) {
    $mimes["svg"] = "image/svg+xml";
    return $mimes;
});

/**
 * Here we adjust content on custom post type archives
 *
 * @return string
 */
add_filter("ahw_post_type_data", function ($post_type_data) {
    return $post_type_data;
});

/**
 * Here we adjust content on taxonomy term archives
 *
 * @return string
 */
add_filter("ahw_taxonomy_term_data", function ($taxonomy_term_data) {
    return $taxonomy_term_data;
});

/**
 * Here we adjust what custom post types should have archives
 *
 * @return string
 */
add_filter("ahw_post_types_with_archives", function ($post_types) {
    return $post_types;
});

/**
 * Here we adjust site meta
 *
 * @return string
 */
add_filter("ahw_site_meta", function ($site_meta) {
    $site_meta = Akka_content::get_site_meta_header($site_meta);
    $site_meta = Akka_content::get_site_meta_cookies($site_meta);
    $site_meta = Akka_content::get_site_meta_footer($site_meta);
    return $site_meta;
});

/**
 * Here we adjust how posts are displayed in archives
 *
 * @return string
 */
add_filter(
    "awh_post_in_archive",
    function ($post_in_archive, $post) {
        return $post_in_archive;
    },
    10,
    2
);

/**
 * Here we adjust the headless plugins enabled blocks
 *
 * @return string
 */
add_filter("ahw_allowed_blocks", function ($allowed_blocks) {
    $allowed_blocks[] = "akka/image-and-text";
    $allowed_blocks[] = "akka/infobox";
    $allowed_blocks[] = "akka/blurbs-horizontal";
    $allowed_blocks[] = "akka/blurb";
    return $allowed_blocks;
});

/**
 * Here we adjust the data for headless get post
 *
 * @return array
 */
add_filter("ahw_post_data", function ($post_data) {
    if (!class_exists("\App\Akka_content")) {
        return $post_data;
    }
    return $post_data;
});

add_action("the_content", function ($content) {
    return $content;
});
