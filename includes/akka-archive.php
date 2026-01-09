<?php
namespace Akka;

class Archive
{
    public static function get_post_type_archive_permalink($post_type)
    {
        return ltrim(Utils::parseUrl(get_post_type_archive_link($post_type)), '/');
    }

    public static function get_post_type_archive($archive_post_type, $page)
    {
        $query = self::archive_query($archive_post_type, $page);

        $posts = Post::posts_to_blurbs($query->posts);

        $post_type_object = get_post_type_object($archive_post_type);

        if (!$post_type_object) {
            return null;
        }

        $post_type_archive = [
            'post_type' => 'post_type',
            'slug' => $archive_post_type,
            'url' => '/' . self::get_post_type_archive_permalink($archive_post_type),
            'post_title' => $post_type_object->label,
            'name' => $post_type_object->label,
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages - $page + 1, // NOTE: Max num pages adjusts to starting page
            'posts' => $posts,
            'next_page' =>
                $query->max_num_pages > $page + 1
                    ? '/' . self::get_post_type_archive_permalink($post_type) . '?page=' . ($page + 1)
                    : null,
        ];

        if ($archive_post_type == 'post') {
            $post_page = get_option('page_for_posts');
            if ($post_page) {
                $post_type_archive = array_merge(Post::get_post_data($post_page), $post_type_archive);
            }
        }

        return apply_filters('akka_post_type_archive', $post_type_archive);
    }

    public static function get_taxonomy_term_archive($archive_taxonomy, $archive_taxonomy_term, $page, $year = null)
    {
        $query_args = [
            'post_type' => $archive_taxonomy->object_type,
            'tax_query' => [
                [
                    'taxonomy' => $archive_taxonomy->name,
                    'field' => 'slug',
                    'terms' => $archive_taxonomy_term->slug,
                ],
            ],
        ];
        if ($year) {
            $query_args['date_query'] = [
                'year' => $year,
            ];
        }

        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = Post::posts_to_blurbs($query->posts);

        $taxonomy_term_archive = [
            'post_type' => 'taxonomy_term',
            'taxonomy' => $archive_taxonomy->name,
            'taxonomy_label' => $archive_taxonomy->labels->singular_name,
            'term_id' => $archive_taxonomy_term->term_id,
            'parent_id' => $archive_taxonomy_term->parent,
            'slug' => $archive_taxonomy_term->slug,
            'url' => Utils::parseUrl(get_term_link($archive_taxonomy_term->term_id)),
            'post_title' => $archive_taxonomy_term->name,
            'description' => term_description($archive_taxonomy_term->term_id),
            'name' => $archive_taxonomy_term->name,
            'fields' => get_fields($archive_taxonomy_term),
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages - $page + 1, // NOTE: Max num pages adjusts to starting page
            'posts' => $posts,
            'next_page' =>
                $query->max_num_pages > $page + 1
                    ? '/' . get_term_link($archive_taxonomy_term->term_id) . '?page=' . ($page + 1)
                    : null,
        ];

        $taxonomy_term_archive['seo_meta'] = Term::get_term_seo_meta($taxonomy_term_archive);

        return apply_filters('akka_taxonomy_term_archive', $taxonomy_term_archive, $archive_taxonomy_term);
    }

    private static function archive_query($post_type, $page = 1)
    {
        $query_args = [
            'post_type' => $post_type,
        ];
        $query_args = apply_filters('akka_' . $post_type . '_archive_query_args', $query_args);

        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        return $query;
    }

    private static function get_posts_query($query_args, $options = [])
    {
        if (isset($options['page']) && $options['page'] > 0) {
            $query_args = self::set_offset_and_per_page($query_args, $options['page']);
        }

        if (isset($query_args['s']) && function_exists('relevanssi_do_query')) {
            return Search::get_relevanssi_query($query_args);
        }

        $query = new \WP_Query($query_args);

        // For relevanssi: recalculate max_num_pages if there is an offset
        if (isset($query_args['offset']) && $query_args['offset'] > 0 && function_exists('relevanssi_do_query')) {
            $query->max_num_pages += $query_args['offset'] / $query_args['posts_per_page'];
        }

        return $query;
    }

    private static function set_offset_and_per_page($query_args, $page = 1)
    {
        if (!isset($query_args['posts_per_page'])) {
            $posts_per_page = get_option('posts_per_page');
            $query_args['posts_per_page'] = $posts_per_page;
        }
        if (!isset($query_args['offset'])) {
            $query_args['offset'] = 0;
        }
        $query_args['offset'] += ((int) $page - 1) * $query_args['posts_per_page'];
        return $query_args;
    }

    public static function get_post_archive($post_types, $category, $post_tag, $per_page = -1, $offset = 0, $page = 1)
    {
        $query_args = [
            'offset' => $offset,
            'post_type' => $post_types,
            'posts_per_page' => $per_page,
            'ignore_sticky_posts' => 1,
        ];

        if ($category || $post_tag) {
            $query_args['tax_query'] = [];
        }
        if ($category) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => [$category],
            ];
        }
        if ($post_tag) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'post_tag',
                'field' => 'slug',
                'terms' => [$post_tag],
            ];
        }

        $query_args = apply_filters('akka_get_posts_args', $query_args);

        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = Post::posts_to_blurbs($query->posts);

        return [
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => $posts,
            'next_page' =>
                $query->max_num_pages > $page + 1
                    ? '/' . self::get_post_type_archive_permalink($post_types[0]) . '?page=' . ($page + 1)
                    : null,
        ];
    }

    public static function get_posts_feed($post_types, $per_page = 50, $offset = 0, $page = 1)
    {
        $query_args = [
            'offset' => $offset,
            'post_type' => $post_types,
            'posts_per_page' => $per_page,
            'ignore_sticky_posts' => 1,
            'date_query' => [
                [
                    'column' => 'post_date',
                    'after' => '12 month ago',
                ],
            ],
        ];

        $query_args = apply_filters('akka_get_posts_args', $query_args);

        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = Post::posts_to_blurbs($query->posts);

        return [
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => $posts,
        ];
    }
}
