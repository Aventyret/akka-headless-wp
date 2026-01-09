<?php
namespace Akka;

class Search
{
    public static function get_relevanssi_query($query_args)
    {
        $query = new \WP_Query();
        $query->parse_query($query_args);
        relevanssi_do_query($query);

        return $query;
    }

    public static function search($query, $post_type, $category_slugs = [], $term_slugs = [], $taxonomy = 'post_tag', $offset = 0, $page = 1)
    {
        if ((empty($query) || strlen($query) < 2) && empty($category_slugs) && empty($term_slugs)) {
            return [
                'count' => 0,
                'pages' => 0,
                'posts' => [],
            ];
        }

        $query_args = [
            'offset' = $offset,
        ];

        if ($query) {
            $query_args = [
                's' => $query,
            ];
        }
        if ($post_type) {
            $query_args['post_type'] = $post_type;
        }
        if (!empty($category_slugs) || !empty($term_slugs)) {
            $query_args['tax_query'] = [];
        }
        if (!empty($category_slugs)) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'category',
                'terms' => $category_slugs,
                'field' => 'slug',
            ];
        }
        if (!empty($term_slugs)) {
            $query_args['tax_query'][] = [
                'taxonomy' => $taxonomy,
                'terms' => $term_slugs,
                'field' => 'slug',
                'operator' => 'AND',
            ];
        }

        $query_args = apply_filters('akka_search_query_args', $query_args);

        $query = Archive::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = Post::posts_to_blurbs($query->posts);

        $search_result = [
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => $posts,
        ];

        return apply_filters('akka_search_result', $search_result);
    }
}
