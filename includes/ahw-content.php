<?php
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_utils as Utils;

class Akka_headless_wp_content
{
    public static function can_get_content()
    {
        return true;
    }

    public static function get_site_meta()
    {
        $menu_ids = get_nav_menu_locations();
        $navigation = [];
        $navigation_meta = [];

        foreach ($menu_ids ?: [] as $menu_slug => $menu_id) {
            $menu_id = apply_filters('ahw_site_meta_menu_id', $menu_id, $menu_slug);
            $slug = $menu_slug;
            $menu_items = wp_get_nav_menu_items($menu_id);
            // Polylang fix for menus
            if (function_exists('pll_current_language') && pll_current_language() != pll_default_language()) {
                if (str_ends_with($slug, '___' . pll_current_language())) {
                    $slug = str_replace('___' . pll_current_language(), '', $slug);
                }
            }
            $navigation[$slug] = null;
            $navigation_meta[$slug] = null;
            if ($menu_items) {
                $navigation[$slug] = array_map(function ($item) {
                    return [
                        'id' => $item->ID,
                        'parent_id' => $item->menu_item_parent ? $item->menu_item_parent : null,
                        'url' => Utils::parseUrl($item->url),
                        'title' => $item->title,
                        'description' => $item->description,
                        'children' => [],
                    ];
                }, $menu_items);

                $navigation[$slug] = array_reduce(
                    $navigation[$slug],
                    function ($menu_items, $menu_item) {
                        if ($menu_item['parent_id']) {
                            $ids = array_column($menu_items, 'id');
                            $parent_index = array_search($menu_item['parent_id'], $ids);
                            if ($parent_index !== false) {
                                $menu_items[$parent_index]['children'][] = $menu_item;
                            }
                        } else {
                            $menu_items[] = $menu_item;
                        }
                        return $menu_items;
                    },
                    []
                );
                $navigation_meta[$slug] = [
                    'name' => wp_get_nav_menu_name($slug),
                ];
            }
        }
        $site_meta = array_merge(self::get_site_meta_global_fields(), [
            'navigation' => $navigation,
            'navigation_meta' => $navigation_meta,
        ]);
        if (class_exists('WPSEO_Redirect_Manager')) {
            $redirect_manager = new WPSEO_Redirect_Manager();
            $redirects = $redirect_manager->get_all_redirects();
            $site_meta['redirects'] = array_reduce(
                $redirects,
                function ($redirects, $r) {
                    $redirect = [
                        'origin' => trim($r->get_origin(), '/'),
                        'target' => rtrim($r->get_target(), '/'),
                        'status_code' => $r->get_type() ?? 301,
                    ];
                    if (!str_starts_with($redirect['target'], '/')) {
                        $redirect['target'] = '/' . $redirect['target'];
                    }
                    if (in_array($r->get_format(), ['regex', 'plain'])) {
                        $redirects[$r->get_format()][] = $redirect;
                    }
                    return $redirects;
                },
                ['plain' => [], 'regex' => []]
            );
        }
        return apply_filters('ahw_site_meta', $site_meta);
    }

    private static function get_site_meta_global_fields()
    {
        $site_meta = [];
        $fields = get_fields('global');
        foreach ($fields ? $fields : [] as $field => $value) {
            if (count(explode('_', $field)) > 2) {
                [$g, $section, $key] = preg_split('/_/', $field, 3, PREG_SPLIT_NO_EMPTY);
                if (isset($section) && isset($key)) {
                    if ($value instanceof \WP_Post) {
                        $permalink = Utils::parseUrl(get_permalink($value));
                        $value = [
                            'post_id' => $value->ID,
                            'permalink' => $permalink,
                            'url' => $permalink,
                            'post_title' => $value->post_title,
                            'post_name' => $value->post_name,
                        ];
                    }
                    $site_meta[$section][$key] = $value;
                }
            }
        }
        $site_meta['header']['posts_url'] = Utils::parseUrl(get_permalink(get_option('page_for_posts')));
        if (isset($site_meta['cookies']) && isset($site_meta['cookies']['details_link'])) {
            $site_meta['cookies']['details_link'] = Utils::parseUrl($site_meta['cookies']['details_link']);
        }
        if (isset($site_meta['cookies']) && isset($site_meta['cookies']['enabled_cookie_types'])) {
            $site_meta['cookies']['enabled_cookie_types'] = array_reduce(
                $site_meta['cookies']['enabled_cookie_types'] ? $site_meta['cookies']['enabled_cookie_types'] : [],
                function ($enabled_types, $cookie_type) {
                    return array_merge($enabled_types, [
                        [
                            'type' => $cookie_type . '_storage',
                            'name' => get_field('global_cookies_' . $cookie_type . '_name', 'global'),
                            'read_only' => false,
                            'default_enabled' => false,
                        ],
                    ]);
                },
                [
                    [
                        'type' => 'necessary_storage',
                        'name' => get_field('global_cookies_necessary_name', 'global'),
                        'read_only' => true,
                        'default_enabled' => true,
                    ],
                ]
            );
        }
        return $site_meta;
    }

    private static function get_post_types_with_archives()
    {
        $post_types = ['post'];
        return apply_filters('ahw_post_types_with_archives', $post_types);
    }

    private static function get_post_type_archive_permalink($post_type)
    {
        return ltrim(Utils::parseUrl(get_post_type_archive_link($post_type)), '/');
    }

    public static function get_post($data)
    {
        $permalink = Utils::getRouteParam($data, 'permalink');

        if (!$permalink) {
            return new WP_REST_Response(['message' => 'Missing permalink'], 400);
        }

        $permalink = urldecode($permalink);

        // Check if multisite front page
        if (is_multisite() && strlen($permalink) == 2) {
            $blog_id = get_id_from_blogname($permalink);
            if ($blog_id && $blog_id != 1) {
                $permalink = '/';
            }
        }

        $post_id = $permalink == '/' ? get_option('page_on_front') : url_to_postid($permalink);

        // Check custom post structure
        if (!$post_id && $permalink != '/') {
            $permalink_parts = explode('/', $permalink);
            $post_object = get_page_by_path(
                $permalink_parts[count($permalink_parts) - 1],
                OBJECT,
                apply_filters('ahw_custom_post_strucure_post_types', ['post', 'page'])
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
                    'redirect' => Utils::parseUrl($correct_permalink),
                ];
            }
        }

        if (!$post_id) {
            $post_id = apply_filters('ahw_post_pre_redirect_post_id', $post_id, $permalink);
            if ($post_id) {
                $redirect_response = null;
            }
        }

        // Is this a post type archive?
        if (!$post_id) {
            $post_type_archive_data = self::get_post_type_archive_data($permalink);
            if ($post_type_archive_data) {
                return $post_type_archive_data;
            }
        }

        // Is this a taxonomy term archive?
        if (!$post_id) {
            $year = Utils::getQueryParam('year', null);
            $taxonomy_term_archive_data = self::get_taxonomy_term_archive_data($permalink, $year);
            if ($taxonomy_term_archive_data) {
                return $taxonomy_term_archive_data;
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
            $post_data = apply_filters('ahw_post_not_found_post_data', $post_id, $permalink);
            if ($post_data) {
                return $post_data;
            }
        }

        if (
            $post_id &&
            function_exists('pll_get_post_language') &&
            pll_get_post_language($post_id) != pll_current_language()
        ) {
            $post_id = null;
        }

        if (!$post_id) {
            return new WP_REST_Response(['message' => 'Post not found'], 404);
        }

        return self::get_post_data($post_id);
    }

    public static function get_posts($data)
    {
        $post_types = explode(',', Utils::getQueryParam('post_type', 'post'));
        $category = Utils::getQueryParam('category', null);
        $post_tag = Utils::getQueryParam('post_tag', null);
        $per_page = Utils::getQueryParam('per_page', -1);
        $offset = Utils::getQueryParam('offset', 0);

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

        $query_args = apply_filters('ahw_get_posts_args', $query_args);

        $page = Utils::getQueryParam('page', 1);
        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = self::parse_posts($query->posts);

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

    public static function get_posts_feed()
    {
        $post_types = explode(',', Utils::getQueryParam('post_type', 'post,podcast,note'));
        $per_page = Utils::getQueryParam('per_page', 50);

        $query_args = [
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

        $query_args = apply_filters('ahw_get_posts_args', $query_args);

        $page = Utils::getQueryParam('page', 1);
        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = self::parse_posts($query->posts);

        return [
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => $posts,
        ];
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

    public static function get_post_by_id($data)
    {
        $post_id = Utils::getRouteParam($data, 'post_id');
        $blog_id = Utils::getRouteParam($data, 'blog_id');
        $get_autosaved = !!Utils::getQueryParam('autosaved');

        if (!$post_id) {
            return new WP_REST_Response(['message' => 'Post not found'], 404);
        }

        if ($blog_id) {
            switch_to_blog($blog_id);
        }

        return self::get_post_data($post_id, ['publish', 'draft', 'private', 'pending'], $get_autosaved);
    }

    public static function get_attachment_by_id($data)
    {
        $attachment_id = Utils::getRouteParam($data, 'attachment_id');

        if (!$attachment_id) {
            return new WP_REST_Response(['message' => 'Attachment not found'], 404);
        }

        $attachment_attributes = Utils::get_attachment_image_src($attachment_id);

        if (!$attachment_attributes) {
            return new WP_REST_Response(['message' => 'Attachment not found'], 404);
        }

        return [
            'attachment_id' => $attachment_id,
            'src' => AKKA_CMS_INTERNAL_BASE . $attachment_attributes[0],
            'width' => isset($attachment_attributes[1]) ? $attachment_attributes[1] : null,
            'height' => isset($attachment_attributes[2]) ? $attachment_attributes[2] : null,
        ];
    }

    public static function get_post_data($post_id, $post_status = ['publish'], $get_autosaved = false)
    {
        global $post;
        $posts = get_posts([
            'post__in' => [$post_id],
            'post_type' => 'any',
            'post_status' => $post_status,
        ]);
        if (empty($posts)) {
            return new WP_REST_Response(['message' => 'Post not found'], 404);
        }
        if ($get_autosaved) {
            $autosaved_post = wp_get_post_autosave($post_id);
            if ($autosaved_post) {
                $posts[0]->post_content = $autosaved_post->post_content;
            }
        }
        $post = $posts[0];

        if ($post->post_password && !in_array('private', $post_status)) {
            return [
                'post_id' => $post->ID,
                'post_type' => $post->post_type,
                'redirect' => '/protected?p=' . $post->ID,
            ];
        }

        $akka_post = self::get_akka_post();
        do_action('ahw_pre_post_content', $akka_post);
        $akka_post['post_content'] = apply_filters('the_content', $post->post_content);
        $akka_post['seo_meta'] = self::get_post_seo_meta(
            $post,
            Resolvers::resolve_field($akka_post['featured_image'], 'id')
        );

        $data = apply_filters('ahw_post_data', $akka_post);
        $data['seo_meta']['schema'] = apply_filters(
            'ahw_post_schema_data',
            Resolvers::resolve_array_field($data['seo_meta'], 'schema'),
            $data
        );

        unset($data['fields']);
        return $data;
    }

    private static $_akka_post_memory = [];
    public static function get_akka_post($post_id = null)
    {
        $p = null;
        if ($post_id) {
            $p = get_post($post_id);
        } else {
            global $post;
            $p = $post;
        }
        if (!$p) {
            return null;
        }
        if (!isset(self::$_akka_post_memory[$p->ID])) {
            $post_thumbnail_id = get_post_thumbnail_id($p->ID);
            $featured_image_attributes = $post_thumbnail_id
                ? Utils::internal_img_attributes($post_thumbnail_id, [
                    'priority' => true,
                ])
                : null;

            $permalink = Utils::parseUrl(str_replace(WP_HOME, '', get_permalink($p->ID)));

            $akka_post = [
                'post_id' => $p->ID,
                'post_date' => get_the_date(get_option('date_format'), $p->ID),
                'post_date_iso' => get_the_date('c', $p->ID),
                'post_title' => $p->post_title,
                'post_type' => $p->post_type,
                'post_password' => $p->post_password,
                'post_parent_id' => $p->post_parent,
                'post_status' => $p->post_status,
                'author' => [
                    'id' => $p->post_author,
                    'name' => get_the_author_meta('display_name', $p->post_author),
                    'url' => AKKA_FRONTEND_BASE . Utils::parseUrl(get_author_posts_url($p->post_author)),
                ],
                'slug' => $p->post_name,
                'excerpt' => post_type_supports($p->post_type, 'excerpt') ? $p->post_excerpt : null,
                'page_template' => Utils::get_page_template_slug($p),
                'featured_image' => $featured_image_attributes,
                'thumbnail_caption' => apply_filters(
                    'ahw_image_caption',
                    get_the_post_thumbnail_caption($p->ID),
                    $post_thumbnail_id
                ),
                'permalink' => $permalink,
                'url' => $permalink,
                'taxonomy_terms' => self::get_post_data_terms($p),
                'fields' => get_fields($p->ID),
            ];
            foreach (['category', 'post_tag'] as $taxonomy_slug) {
                if (isset($data['taxonomy_terms'][$taxonomy_slug])) {
                    $akka_post['primary_' . str_replace('post_tag', 'tag', $taxonomy_slug)] =
                        $akka_post['taxonomy_terms'][$taxonomy_slug]['primary_term'];
                }
            }
            if (
                $akka_post['post_type'] == 'page' &&
                self::get_post_type_archive_permalink('post') == $akka_post['slug']
            ) {
                $page = Utils::getQueryParam('page', 1);
                $archive_query = self::archive_query('post', $page);
                $akka_post['archive'] = [
                    'count' => $archive_query->found_posts,
                    'pages' => $archive_query->max_num_pages - $page + 1, // NOTE: Max num pages adjusts to starting page
                    'posts' => self::parse_posts($archive_query->posts),
                    'next_page' =>
                        $archive_query->max_num_pages > $page + 1
                            ? '/' . self::get_post_type_archive_permalink('post') . '?page=' . ($page + 1)
                            : null,
                ];
            }
            self::$_akka_post_memory[$p->ID] = $akka_post;
        }
        return self::$_akka_post_memory[$p->ID];
    }

    public static function get_akka_posts($query_args)
    {
        return self::parse_posts(get_posts($query_args));
    }

    private static function get_post_type_archive_data($permalink)
    {
        $archive_post_type = null;
        foreach (self::get_post_types_with_archives() as $post_type) {
            if ($permalink == self::get_post_type_archive_permalink($post_type)) {
                $archive_post_type = $post_type;
            }
        }
        if (!$archive_post_type) {
            return null;
        }

        $page = Utils::getQueryParam('page', 1);
        $query = self::archive_query($archive_post_type, $page);

        $posts = self::parse_posts($query->posts);

        $post_type_object = get_post_type_object($archive_post_type);

        $post_type_data = [
            'post_type' => 'post_type',
            'slug' => $archive_post_type,
            'url' => '/' . $permalink,
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
                $post_type_data = array_merge(self::get_post_data($post_page), $post_type_data);
            }
        }

        return apply_filters('ahw_post_type_data', $post_type_data);
    }

    private static function archive_query($post_type, $page = 1)
    {
        $query_args = [
            'post_type' => $post_type,
        ];
        $query_args = apply_filters('ahw_' . $post_type . '_archive_query_args', $query_args);

        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        return $query;
    }

    private static function get_taxonomy_term_archive_data($permalink, $year = null)
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

        $page = Utils::getQueryParam('page', 1);
        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = self::parse_posts($query->posts);

        $taxonomy_term_data = [
            'post_type' => 'taxonomy_term',
            'taxonomy' => $archive_taxonomy->name,
            'taxonomy_label' => $archive_taxonomy->labels->singular_name,
            'term_id' => $archive_taxonomy_term->term_id,
            'parent_id' => $archive_taxonomy_term->parent,
            'slug' => $archive_taxonomy_term->slug,
            'url' => '/' . $permalink,
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

        $taxonomy_term_data['seo_meta'] = self::get_term_seo_meta($taxonomy_term_data);

        return apply_filters('ahw_taxonomy_term_data', $taxonomy_term_data, $archive_taxonomy_term);
    }

    private static function get_post_data_terms($post)
    {
        $post_type_taxonomy_map = apply_filters('ahw_headless_post_type_taxonomy_map', [
            'post' => ['category', 'post_tag'],
        ]);
        $taxonomies = isset($post_type_taxonomy_map[$post->post_type]) ? $post_type_taxonomy_map[$post->post_type] : [];
        return self::get_post_terms($post, $taxonomies);
    }

    private static function get_post_in_archive_terms($post)
    {
        $post_type_taxonomy_map = apply_filters('ahw_headless_in_archive_post_type_taxonomy_map', []);
        $taxonomies = isset($post_type_taxonomy_map[$post->post_type]) ? $post_type_taxonomy_map[$post->post_type] : [];
        return self::get_post_terms($post, $taxonomies);
    }

    private static function get_post_terms($post, $taxonomies)
    {
        return array_reduce(
            $taxonomies,
            function ($terms, $taxonomy_slug) use ($post) {
                $taxonomy = get_taxonomy($taxonomy_slug);
                $taxonomy_terms = get_the_terms($post, $taxonomy_slug);
                $terms[$taxonomy_slug] = [
                    'taxonomy' => [
                        'name' => $taxonomy->label,
                        'name_singular' => $taxonomy->labels->singular_name,
                        'slug' => $taxonomy_slug,
                    ],
                    'terms' => array_map(
                        function ($term) use ($taxonomy) {
                            $term_url = Utils::parseUrl(get_term_link($term->term_id));
                            return [
                                'term_id' => $term->term_id,
                                'parent_id' => $term->parent,
                                'name' => $term->name,
                                'slug' => $term->slug,
                                'url' => apply_filters('ahw_term_url', $term_url, $term, $taxonomy),
                            ];
                        },
                        $taxonomy_terms ? $taxonomy_terms : []
                    ),
                ];
                $terms[$taxonomy_slug]['primary_term'] = self::get_primary_term(
                    $taxonomy_slug,
                    $terms[$taxonomy_slug]['terms'],
                    $post
                );
                return $terms;
            },
            []
        );
    }

    public static function get_terms($taxonomy_slug)
    {
        $taxonomy_terms = get_terms(['taxonomy' => $taxonomy_slug, 'hide_empty' => false]);
        return array_map(
            function ($term) {
                $term_url = Utils::parseUrl(get_term_link($term->term_id));
                return [
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'url' => apply_filters('ahw_term_url', $term_url, $term, $taxonomy),
                ];
            },
            $taxonomy_terms ? $taxonomy_terms : []
        );
    }

    /***
     * Typically not used since taxonomy term archives are accessed by permalink
     */
    public static function get_term($data)
    {
        $taxonomy_slug = str_replace('-', '_', Utils::getRouteParam($data, 'taxonomy_slug'));
        $term_slug = Utils::getRouteParam($data, 'term_slug');

        $term = get_term_by('slug', $term_slug, $taxonomy_slug);

        if (!$term) {
            return new WP_REST_Response(['message' => 'Term not found'], 404);
        }

        $query_args = [
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy_slug,
                    'field' => 'slug',
                    'terms' => $term_slug,
                ],
            ],
        ];
        if (Utils::getQueryParam('per_page', null)) {
            $query_args['posts_per_page'] = Utils::getQueryParam('per_page', null);
        }

        $page = Utils::getQueryParam('page', 1);
        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        return [
            'term_id' => $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
            'count' => $term->count,
            'pages' => $query->max_num_pages - $page + 1, // NOTE: Max num pages adjusts to starting page
            'posts' => self::parse_posts($query->posts),
            'next_page' => '/term/' . $taxonomy_slug . '/' . $term->slug . '/' . ($page + 1),
        ];
    }

    /***
     * Typically not used since author archives are accessed by permalink
     */
    public static function get_author($data)
    {
        $author_slug = isset($data['author_slug']) ? $data['author_slug'] : false;
        $offset = Utils::getQueryParam('offset', 0);

        if (!$author_slug) {
            return new WP_REST_Response(['message' => 'Missing author slug'], 400);
        }

        $author_slug = urldecode($author_slug);

        $author = get_user_by('slug', $author_slug);

        if (!$author) {
            return new WP_REST_Response(['message' => 'Author not found'], 404);
        }

        $query_args = [
            'author' => $author->data->ID,
            'offset' => $offset,
        ];
        if (Utils::getQueryParam('per_page', null)) {
            $query_args['posts_per_page'] = Utils::getQueryParam('per_page', null);
        }

        $query = self::get_posts_query($query_args, [
            'page' => Utils::getQueryParam('page', 1),
        ]);

        return [
            'author_id' => $author->data->ID,
            'slug' => $author_slug,
            'name' => $author->data->user_nicename,
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => self::parse_posts($query->posts),
        ];
    }

    private static function get_posts_query($query_args, $options = [])
    {
        if (isset($options['page']) && $options['page'] > 0) {
            $query_args = self::set_offset_and_per_page($query_args, $options['page']);
        }

        if (isset($query_args['s']) && function_exists('relevanssi_do_query')) {
            return self::get_relevanssi_query($query_args);
        }

        $query = new WP_Query($query_args);

        // For relevanssi: recalculate max_num_pages if there is an offset
        if (isset($query_args['offset']) && $query_args['offset'] > 0 && function_exists('relevanssi_do_query')) {
            $query->max_num_pages += $query_args['offset'] / $query_args['posts_per_page'];
        }

        return $query;
    }

    private static function get_relevanssi_query($query_args)
    {
        $query = new WP_Query();
        $query->parse_query($query_args);
        relevanssi_do_query($query);

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

    public static function parse_posts($posts)
    {
        $post_datas = array_map(function ($post) {
            if (is_array($post)) {
                return $post;
            }
            return self::get_post_in_archive($post);
        }, $posts);
        return $post_datas;
    }

    public static function get_post_in_archive($post)
    {
        if (is_array($post)) {
            return $post;
        }
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_attributes = $thumbnail_id
            ? Utils::internal_img_attributes($thumbnail_id, [
                // NOTE: Handle both ahw_ and awh_ prefix for legacy reasons
                'size' => apply_filters(
                    'ahw_post_in_archive_image_size',
                    apply_filters('awh_post_in_archive_image_size', 'full')
                ),
            ])
            : null;

        $post_in_archive = [
            'post_id' => $post->ID,
            'post_guid' => $post->guid,
            'post_date' => get_the_date(get_option('date_format'), $post->ID),
            'post_date_iso' => get_the_date('c', $post->ID),
            'url' => Utils::parseUrl(get_permalink($post->ID)),
            'featured_image' => !empty($thumbnail_attributes) ? $thumbnail_attributes : null,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'slug' => $post->post_name,
            'description' => get_the_excerpt($post->ID),
            'taxonomy_terms' => self::get_post_in_archive_terms($post),
        ];
        // NOTE: Handle both ahw_ and awh_ prefix for legacy reasons
        return apply_filters('ahw_post_in_archive', apply_filters('awh_post_in_archive', $post_in_archive, $post));
    }

    public static function search($data)
    {
        $query = urldecode(Utils::getRouteParam($data, 'query'));
        $post_type = Utils::getRouteParam($data, 'post_type');
        if ($post_type) {
            $post_type = explode(',', $post_type);
        }
        $category_slugs = Utils::getRouteParam($data, 'category_slugs');
        if ($category_slugs) {
            $category_slugs = explode(',', $category_slugs);
        }
        $term_slugs = Utils::getRouteParam($data, 'term_slugs');
        if ($term_slugs) {
            $term_slugs = explode(',', $term_slugs);
        }
        $taxonomy = Utils::getRouteParam($data, 'taxonomy', 'post_tag');

        if ((empty($query) || strlen($query) < 2) && empty($category_slugs) && empty($term_slugs)) {
            return [
                'count' => 0,
                'pages' => 0,
                'posts' => [],
            ];
        }

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

        $query_args = apply_filters('ahw_search_query_args', $query_args);

        $page = Utils::getQueryParam('page', 1);
        $query = self::get_posts_query($query_args, [
            'page' => $page,
        ]);

        $posts = self::parse_posts($query->posts);

        $search_result_data = [
            'count' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'posts' => $posts,
        ];

        return apply_filters('ahw_search_result_data', $search_result_data);
    }

    private static function get_post_seo_meta($post, $post_thumbnail_id = null)
    {
        $seo_meta = [];
        $specific_seo_image_is_defined = false;
        if (function_exists('the_seo_framework')) {
            $seo_fields = [
                'seo_title' => '_genesis_title',
                'seo_description' => '_genesis_description',
                'seo_image_id' => '_social_image_id',
                'og_title' => '_open_graph_title',
                'og_description' => '_open_graph_description',
                'twitter_title' => '_twitter_title',
                'twitter_description' => '_twitter_description',
            ];
            foreach ($seo_fields as $seo_attr => $meta_key) {
                $meta_value = get_post_meta($post->ID, $meta_key, true);
                if ($meta_value) {
                    $seo_meta[$seo_attr] = $meta_value;
                }
            }
        }
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $yoast_class = YoastSEO()->classes->get(Yoast\WP\SEO\Surfaces\Meta_Surface::class);
            $yoast_meta = $yoast_class->for_post($post->ID);
            $yoast_data = $yoast_meta->get_head()->json;
            $seo_meta = [
                'seo_title' => $yoast_data['title'],
                'og_locale' => $yoast_data['og_locale'],
                'og_type' => $yoast_data['og_type'],
                'canonical_url' => $yoast_data['og_url'],
                'og_site_name' => $yoast_data['og_site_name'],
            ];
            if (isset($yoast_data['description'])) {
                $seo_meta['seo_description'] = $yoast_data['description'];
            }
            if (isset($yoast_data['og_description'])) {
                $seo_meta['og_description'] = $yoast_data['og_description'];
            }
            if (isset($yoast_data['og_image']) && !empty($yoast_data['og_image'])) {
                $seo_meta['seo_image_url'] = $yoast_data['og_image'][0]['url'];
                $seo_meta['seo_image_width'] = $yoast_data['og_image'][0]['width'];
                $seo_meta['seo_image_height'] = $yoast_data['og_image'][0]['height'];
            }
            if (isset($yoast_data['robots'])) {
                $seo_meta['robots'] = [
                    'index' =>
                        isset($yoast_data['robots']['index']) && $yoast_data['robots']['index'] == 'noindex'
                            ? false
                            : true,
                    'follow' =>
                        isset($yoast_data['robots']['follow']) && $yoast_data['robots']['follow'] == 'nofollow'
                            ? false
                            : true,
                ];
            }
            if (Resolvers::resolve_field($yoast_data, 'schema')) {
                $seo_meta['schema'] = [];
                foreach (Resolvers::resolve_array_field($yoast_data['schema'], '@graph') as $graph_data) {
                    if (in_array($graph_data['@type'], ['WebSite', 'Organization'])) {
                        $schema_item = [
                            '@context' => $yoast_data['schema']['@context'],
                            '@type' => $graph_data['@type'],
                            '@id' => AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['@id']),
                            'name' => $graph_data['name'],
                            'url' => AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['url']),
                        ];
                        if ($graph_data['@type'] == 'WebSite') {
                            $schema_item['description'] = Resolvers::resolve_field($graph_data, 'description');
                            $schema_item['inLanguage'] = Resolvers::resolve_field($graph_data, 'inLanguage');
                            if ($search_page_url = apply_filters('ahw_schema_search_page_url', null)) {
                                $schema_item['potentialAction'] = [
                                    '@type' => 'SearchAction',
                                    'target' => [
                                        '@type' => 'EntryPoint',
                                        'urlTemplate' => $search_page_url . '{search_term_string}',
                                    ],
                                    'query-input' => 'required name=search_term_string',
                                ];
                            }
                        }
                        if ($graph_data['@type'] == 'Organization') {
                            $schema_item['@type'] = apply_filters(
                                'ahw_schema_organization_schema_type',
                                'Organization'
                            );
                            if ($search_page_url = apply_filters('ahw_schema_search_page_url', null)) {
                                $schema_item['potentialAction'] = [
                                    '@type' => 'SearchAction',
                                    'target' => [
                                        '@type' => 'EntryPoint',
                                        'urlTemplate' => $search_page_url . '{search_term_string}',
                                    ],
                                    'query-input' => 'required name=search_term_string',
                                ];
                            }
                            if (Resolvers::resolve_field($graph_data, 'logo')) {
                                $schema_item['logo'] = $graph_data['logo'];
                                $schema_item['logo']['@id'] =
                                    AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['logo']['@id']);
                                $schema_item['logo']['url'] = apply_filters(
                                    'ahw_schema_organization_schema_logo_url',
                                    str_replace('wp-content/', 'app/', $graph_data['logo']['url'])
                                );
                                $schema_item['logo']['contentUrl'] = $schema_item['logo']['url'];
                            }
                            if (!empty(Resolvers::resolve_array_field($graph_data, 'sameAs'))) {
                                $schema_item['sameAs'] = $graph_data['sameAs'][0];
                            }
                            if ($contact_pont = apply_filters('ahw_schema_organization_contact_point', null)) {
                                $schema_item['contactPoint'] = $contact_pont;
                            }
                        }
                        $seo_meta['schema'][] = $schema_item;
                    }
                }
            }
        }
        if (is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
            global $wpdb;
            $title = new AIOSEO\Plugin\Common\Meta\Title();
            $description = new AIOSEO\Plugin\Common\Meta\Description();
            $seo_meta = [
                'seo_title' => $title->getTitle($post),
                'seo_description' => $description->getDescription($post),
            ];
            $aio_seo_meta = $wpdb->get_results(
                sprintf(
                    'SELECT title, description, canonical_url, og_title, og_description, og_image_url, og_image_width, og_image_height, twitter_title, twitter_description, robots_noindex, robots_nofollow FROM ' .
                        $wpdb->prefix .
                        'aioseo_posts WHERE post_id = %d',
                    $post->ID
                )
            );
            if (!empty($aio_seo_meta)) {
                if ($aio_seo_meta[0]->canonical_url) {
                    $seo_meta['seo_canonical_url'] = $aio_seo_meta[0]->canonical_url;
                }
                if ($aio_seo_meta[0]->og_title) {
                    $seo_meta['og_title'] = $aio_seo_meta[0]->og_title;
                }
                if ($aio_seo_meta[0]->og_description) {
                    $seo_meta['og_description'] = $aio_seo_meta[0]->og_description;
                }
                if ($aio_seo_meta[0]->twitter_title) {
                    $seo_meta['twitter_title'] = $aio_seo_meta[0]->twitter_title;
                }
                if ($aio_seo_meta[0]->twitter_description) {
                    $seo_meta['twitter_description'] = $aio_seo_meta[0]->twitter_description;
                }
                if ($aio_seo_meta[0]->og_image_url) {
                    $seo_meta['seo_image_url'] = $aio_seo_meta[0]->og_image_url;
                    $seo_meta['seo_image_width'] = $aio_seo_meta[0]->og_image_width
                        ? $aio_seo_meta[0]->og_image_width
                        : 1200;
                    $seo_meta['seo_image_height'] = $aio_seo_meta[0]->og_image_height
                        ? $aio_seo_meta[0]->og_image_height
                        : 630;
                }
                $seo_meta['robots'] = [
                    'index' => $aio_seo_meta[0]->robots_noindex != '1',
                    'follow' => $aio_seo_meta[0]->robots_nofollow != '1',
                ];
            }
        }
        if (!isset($seo_meta['seo_title']) || !$seo_meta['seo_title']) {
            $seo_meta['seo_title'] = $post->post_title;
        }
        if (!isset($seo_meta['seo_description']) || !$seo_meta['seo_description']) {
            $seo_meta['seo_description'] = get_the_excerpt($post->ID);
        }
        $seo_meta['seo_description'] = apply_filters('ahw_seo_description', $seo_meta['seo_description'], $post);
        if (!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) {
            $specific_seo_image_is_defined = true;
        }
        if (
            (!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) &&
            (!isset($seo_meta['seo_image_url']) || !$seo_meta['seo_image_url']) &&
            $post_thumbnail_id
        ) {
            $seo_meta['seo_image_id'] = $post_thumbnail_id;
        }
        if (isset($seo_meta['seo_image_id'])) {
            $image_src = Utils::get_attachment_image_src($seo_meta['seo_image_id'], 'large');
            $seo_meta['seo_image_url'] = $image_src[0];
            $seo_meta['seo_image_width'] = $image_src[1];
            $seo_meta['seo_image_height'] = $image_src[2];
        }
        if (!isset($seo_meta['og_title']) || !$seo_meta['og_title']) {
            $seo_meta['og_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['og_description']) || !$seo_meta['og_description']) {
            $seo_meta['og_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['twitter_title']) || !$seo_meta['twitter_title']) {
            $seo_meta['twitter_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['twitter_description']) || !$seo_meta['twitter_description']) {
            $seo_meta['twitter_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['canonical_url']) || !$seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = wp_get_canonical_url($post->ID);
        }
        if (isset($seo_meta['canonical_url']) && $seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = rtrim(
                str_replace(WP_HOME, AKKA_FRONTEND_BASE, $seo_meta['canonical_url']),
                '/'
            );
        }
        $seo_meta['published_date'] = get_the_date('c', $post->ID);
        $seo_meta['modified_date'] = get_the_modified_date('c', $post->ID);
        if (isset($seo_meta['seo_image_url']) && strpos($seo_meta['seo_image_url'], '/') === 0) {
            $seo_meta['seo_image_url'] = WP_HOME . $seo_meta['seo_image_url'];
        }
        foreach (['seo_title', 'og_title', 'twitter_title'] as $title_key) {
            if (isset($seo_meta[$title_key])) {
                $seo_meta[$title_key] = str_replace(
                    ['&shy;', '&ndash;', '&amp;'],
                    ['', '-', '&'],
                    $seo_meta[$title_key]
                );
            }
        }
        return apply_filters('ahw_seo_meta', $seo_meta, $post, $specific_seo_image_is_defined);
    }

    private static function get_term_seo_meta($term_data)
    {
        $seo_meta = [];
        $specific_seo_image_is_defined = false;
        $specific_seo_description_is_defined = false;
        if (function_exists('the_seo_framework')) {
            // TODO: Support SEO framework
        }
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $yoast_class = YoastSEO()->classes->get(Yoast\WP\SEO\Surfaces\Meta_Surface::class);
            $yoast_meta = $yoast_class->for_term($term_data['term_id']);
            $yoast_data = $yoast_meta->get_head()->json;
            $seo_meta = [
                'seo_title' => $yoast_data['title'],
                'og_locale' => $yoast_data['og_locale'],
                'og_type' => $yoast_data['og_type'],
                'canonical_url' => $yoast_data['og_url'],
                'og_site_name' => $yoast_data['og_site_name'],
            ];
            if (isset($yoast_data['description'])) {
                $seo_meta['seo_description'] = $yoast_data['description'];
            }
            if (isset($yoast_data['og_description'])) {
                $seo_meta['og_description'] = $yoast_data['og_description'];
            }
            if (isset($yoast_data['og_image']) && !empty($yoast_data['og_image'])) {
                $seo_meta['seo_image_url'] = $yoast_data['og_image'][0]['url'];
                $seo_meta['seo_image_width'] = $yoast_data['og_image'][0]['width'];
                $seo_meta['seo_image_height'] = $yoast_data['og_image'][0]['height'];
                $specific_seo_image_is_defined = true;
            }
        }
        if (!isset($seo_meta['seo_title']) || !$seo_meta['seo_title']) {
            $seo_meta['seo_title'] = $term_data['post_title'];
        }
        if (!isset($seo_meta['seo_description']) || !$seo_meta['seo_description']) {
            $seo_meta['seo_description'] = term_description($term_data['term_id']);
            $specific_seo_description_is_defined = true;
        }
        if (isset($seo_meta['seo_image_id'])) {
            $image_src = Utils::get_attachment_image_src($seo_meta['seo_image_id'], 'large');
            $seo_meta['seo_image_url'] = $image_src[0];
            $seo_meta['seo_image_width'] = $image_src[1];
            $seo_meta['seo_image_height'] = $image_src[2];
        }
        if (!isset($seo_meta['og_title']) || !$seo_meta['og_title']) {
            $seo_meta['og_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['og_description']) || !$seo_meta['og_description']) {
            $seo_meta['og_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['twitter_title']) || !$seo_meta['twitter_title']) {
            $seo_meta['twitter_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['twitter_description']) || !$seo_meta['twitter_description']) {
            $seo_meta['twitter_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['canonical_url']) || !$seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = get_term_link($term_data['term_id']);
        }
        if (isset($seo_meta['canonical_url']) && $seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = rtrim(
                str_replace(WP_HOME, AKKA_FRONTEND_BASE, $seo_meta['canonical_url']),
                '/'
            );
        }
        if (isset($seo_meta['seo_image_url']) && strpos($seo_meta['seo_image_url'], '/') === 0) {
            $seo_meta['seo_image_url'] = WP_HOME . $seo_meta['seo_image_url'];
        }
        foreach (['seo_title', 'og_title', 'twitter_title'] as $title_key) {
            if (isset($seo_meta[$title_key])) {
                $seo_meta[$title_key] = str_replace(
                    ['&shy;', '&ndash;', '&amp;'],
                    ['', '-', '&'],
                    $seo_meta[$title_key]
                );
            }
        }
        return apply_filters(
            'ahw_term_seo_meta',
            $seo_meta,
            $term_data,
            $specific_seo_image_is_defined,
            $specific_seo_description_is_defined
        );
    }

    private static function get_primary_term($taxonomy, $terms, $post)
    {
        if (empty($terms)) {
            return null;
        }
        if (count($terms) > 1 && function_exists('yoast_get_primary_term_id')) {
            $term_id = yoast_get_primary_term_id($taxonomy, $post);
            $term_index = array_search($term_id, array_column($terms, 'id'));
            if ($term_index !== false) {
                return $terms[$term_index];
            }
        }
        return $terms[0];
    }
}
