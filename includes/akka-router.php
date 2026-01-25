<?php
namespace Akka;

class Router
{
    public static function can_get_content()
    {
        return true;
    }

    public static function permalink_request($data)
    {
        $permalink = Utils::get_route_param($data, 'permalink');

        if (!$permalink) {
            return new \WP_REST_Response(['message' => 'Missing permalink'], 400);
        }

        $permalink = urldecode($permalink);

        return self::get_content_from_permalink($permalink);
    }

    public static function posts_request($data)
    {
        $post_types = explode(',', Utils::get_query_param('post_type', 'post'));
        $category = Utils::get_query_param('category', null);
        $post_tag = Utils::get_query_param('post_tag', null);
        $per_page = Utils::get_query_param('per_page', -1);
        $offset = Utils::get_query_param('offset', 0);
        $page = Utils::get_query_param('page', 1);

        return Archive::get_post_archive($post_types, $category, $post_tag, $per_page, $offset, $page);
    }

    public static function feed_request($data)
    {
        $post_types = explode(',', Utils::get_query_param('post_type', 'post,podcast,note'));
        $per_page = Utils::get_query_param('per_page', 50);
        $offset = Utils::get_query_param('offset', 0);
        $page = Utils::get_query_param('page', 1);

        return Archive::get_posts_feed($post_types, $per_page, $offset, $page);
    }

    public static function search_request($data)
    {
        $query = urldecode(Utils::get_route_param($data, 'query'));
        $post_type = Utils::get_route_param($data, 'post_type');
        if ($post_type) {
            $post_type = explode(',', $post_type);
        }
        $category_slugs = Utils::get_route_param($data, 'category_slugs');
        if ($category_slugs) {
            $category_slugs = explode(',', $category_slugs);
        }
        $term_slugs = Utils::get_route_param($data, 'term_slugs');
        if ($term_slugs) {
            $term_slugs = explode(',', $term_slugs);
        }
        $taxonomy = Utils::get_route_param($data, 'taxonomy', 'post_tag');
        $offset = Utils::get_query_param('offset', 0);
        $page = Utils::get_query_param('page', 1);

        return Search::search($query, $post_type, $category_slugs, $term_slugs, $taxonomy, $offset, $page);
    }

    private static function get_content_from_permalink($permalink)
    {
        // Check if multisite and remove sub site prefix from permalink
        if (is_multisite() && (strlen($permalink) == 2 || strpos($permalink, '/') == 2)) {
            $lang_in_permalink = substr($permalink, 0, 2);
            $blog_id = get_id_from_blogname($lang_in_permalink);
            if ($blog_id && $blog_id != 1) {
                $permalink = substr($permalink, 3);
                if (!$permalink) {
                    // Front page permalink is '/' and not empty string in Akka
                    $permalink = '/';
                }
            }
        }

        $post_id = $permalink == '/' ? get_option('page_on_front') : url_to_postid($permalink);

        // Check custom post structure
        if (!$post_id && $permalink != '/') {
            $permalink_parts = explode('/', $permalink);
            $post_object = get_page_by_path(
                $permalink_parts[count($permalink_parts) - 1],
                OBJECT,
                apply_filters('akka_custom_post_strucure_post_types', ['post', 'page'])
            );
            if ($post_object && $post_object->post_type !== 'attachment') {
                $post_id = $post_object->ID;
            }
        }

        // Check hierarchical page
        if (!$post_id) {
            $post_id = self::get_hierarchical_page_id($permalink);
        }

        // Fix for non public post types
        if ($post_id && $permalink != '/' && strpos($permalink, '/') !== false) {
            $maybe_post_type_rewrite_slug = substr($permalink, 0, strpos($permalink, '/'));
            foreach (get_post_types([], 'objects') as $post_type) {
                $rewite_slug = Resolvers::resolve_field($post_type->rewrite, 'slug');
                if ($maybe_post_type_rewrite_slug == $rewite_slug && !$post_type->public) {
                    $post_id = null;
                }
            }
        }

        // Check that this is the correct permalink
        $redirect_response = null;
        if ($post_id && strpos(get_permalink($post_id), $permalink) === false) {
            $correct_permalink = get_permalink($post_id);
            if (strpos($correct_permalink, '?page_id=') === false && strpos($correct_permalink, '?p=') === false) {
                $post_id = null;
                $redirect_response = [
                    'post_type' => 'redirect',
                    'redirect' => Utils::parse_url($correct_permalink),
                ];
            }
        }

        if (!$post_id) {
            $post_id = apply_filters('akka_post_pre_redirect_post_id', $post_id, $permalink);
            if ($post_id) {
                $redirect_response = null;
            }
        }

        // Is this a post type archive?
        if (!$post_id) {
            $post_type_archive = self::get_post_type_archive_from_permalink($permalink);
            if ($post_type_archive) {
                return $post_type_archive;
            }
        }

        // Is this a taxonomy term archive?
        if (!$post_id) {
            $taxonomy_term_archive = self::get_taxonomy_term_archive_from_permalink($permalink);
            if ($taxonomy_term_archive) {
                return $taxonomy_term_archive;
            }
        }

        // Try polylang translated front page
        if (!$post_id && function_exists('pll_home_url') && strlen($permalink) == 2) {
            $front_page_translations = pll_get_post_translations(get_option('page_on_front'));
            if (isset($front_page_translations[$permalink])) {
                $post_id = $front_page_translations[$permalink];
            }
        }

        // Try polylang page
        if (!$post_id && function_exists('pll_current_language')) {
            if (!$post_id && pll_current_language() == pll_default_language()) {
                $page = get_page_by_path($permalink);
                if ($page) {
                    $post_id = $page->ID;
                }
            }
            if (!$post_id && str_starts_with($permalink, pll_current_language() . '/')) {
                $page = get_page_by_path(substr($permalink, 3));
                if ($page) {
                    $post_id = $page->ID;
                }
            }
            if (!$post_id && str_starts_with($permalink, pll_current_language() . '/')) {
                $post_id = url_to_postid(substr($permalink, 3));
            }
        }

        // Check that post is published
        if ($post_id && get_post_status($post_id) != 'publish') {
            $post_id = null;
        }

        if (!$post_id) {
            if ($redirect_response) {
                return $redirect_response;
            }
            $post_data = apply_filters('akka_post_not_found_post_data', $post_id, $permalink);
            if ($post_data) {
                return $post_data;
            }
        }

        // Polylang extra fix
        if (
            $post_id &&
            function_exists('pll_get_post_language') &&
            pll_get_post_language($post_id) != pll_current_language()
        ) {
            $post_id = null;
        }

        if (!$post_id) {
            return new \WP_REST_Response(['message' => 'Post not found'], 404);
        }

        $p = Post::get_single($post_id);

        if (!$p) {
            return new \WP_REST_Response(['message' => 'Post not found'], 404);
        }

        return $p;
    }

    private static function get_post_types_with_archives()
    {
        $post_types = ['post'];
        return apply_filters('akka_post_types_with_archives', $post_types);
    }

    private static function get_post_type_archive_from_permalink($permalink)
    {
        $archive_post_type = null;
        foreach (self::get_post_types_with_archives() as $post_type) {
            if ($permalink == Archive::get_post_type_archive_permalink($post_type)) {
                $archive_post_type = $post_type;
            }
        }
        if (!$archive_post_type) {
            return null;
        }

        $page = Utils::get_query_param('page', 1);

        return Archive::get_post_type_archive($archive_post_type, $page);
    }

    private static function get_taxonomy_term_archive_from_permalink($permalink)
    {
        $archive_taxonomy = null;
        foreach (get_taxonomies([], 'objects') as $taxonomy) {
            if (
                $taxonomy->rewrite &&
                isset($taxonomy->rewrite['slug']) &&
                str_starts_with($permalink, $taxonomy->rewrite['slug'] . '/')
            ) {
                $archive_taxonomy = $taxonomy;
            }
        }
        if (!$archive_taxonomy) {
            return null;
        }
        $permalink_parts = explode('/', $permalink);
        $archive_taxonomy_term = get_term_by(
            'slug',
            $permalink_parts[count($permalink_parts) - 1],
            $archive_taxonomy->name
        );
        if (!$archive_taxonomy_term) {
            return null;
        }

        $page = Utils::get_query_param('page', 1);
        $year = Utils::get_query_param('year', null);

        return Archive::get_taxonomy_term_archive($archive_taxonomy, $archive_taxonomy_term, $page);
    }

    private static function get_hierarchical_page_id($permalink)
    {
        global $wpdb;
        $permalink_parts = explode('/', $permalink);
        if (count($permalink_parts) < 2) {
            return null;
        }
        $page_result = $wpdb->get_results(
            sprintf(
                'SELECT ID, post_name, post_parent FROM ' .
                    $wpdb->prefix .
                    "posts WHERE post_name = '%s' and post_parent > 0 and post_type = 'page'",
                $permalink_parts[count($permalink_parts) - 1]
            )
        );
        if (empty($page_result)) {
            return null;
        }
        $parent_result = $wpdb->get_results(
            sprintf(
                'SELECT ID, post_name, post_parent FROM ' .
                    $wpdb->prefix .
                    "posts WHERE post_name = '%s' and post_type = 'page'",
                $permalink_parts[count($permalink_parts) - 2]
            )
        );
        if (empty($page_result)) {
            return null;
        }
        return $page_result[0]->ID;
    }

    public static function post_by_id_request($data)
    {
        $post_id = Utils::get_route_param($data, 'post_id');
        $blog_id = Utils::get_route_param($data, 'blog_id');
        $get_autosaved = !!Utils::get_query_param('autosaved');

        if (!$post_id) {
            return new \WP_REST_Response(['message' => 'Post not found'], 404);
        }

        if ($blog_id) {
            switch_to_blog($blog_id);
        }

        $p = Post::get_single($post_id, ['publish', 'draft', 'private', 'pending'], $get_autosaved);

        if (!$p) {
            return new \WP_REST_Response(['message' => 'Post not found'], 404);
        }

        return $p;
    }

    public static function attachment_by_id_request($data)
    {
        $attachment_id = Utils::get_route_param($data, 'attachment_id');

        if (!$attachment_id) {
            return new \WP_REST_Response(['message' => 'Attachment not found'], 404);
        }

        $attachment_attributes = Utils::get_attachment_image_src($attachment_id);

        if (!$attachment_attributes) {
            return new \WP_REST_Response(['message' => 'Attachment not found'], 404);
        }

        return [
            'attachment_id' => $attachment_id,
            'src' => AKKA_CMS_INTERNAL_BASE . $attachment_attributes[0],
            'width' => isset($attachment_attributes[1]) ? $attachment_attributes[1] : null,
            'height' => isset($attachment_attributes[2]) ? $attachment_attributes[2] : null,
        ];
    }
}
