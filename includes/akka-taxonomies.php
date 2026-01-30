<?php
namespace Akka;

class Taxonomies
{
    public static function register_taxonomy($taxonomy_slug, $args, $options = [])
    {
        $options = array_merge(
            [
                'post_types' => ['post'],
                'in_archive_post_types' => [],
                'admin_column_post_types' => [],
                'admin_filter_post_types' => [],
                'has_archive' => false,
                'has_single' => false,
                'acf_field_groups' => [],
            ],
            $options
        );

        $args = array_merge(
            [
                'label' => null,
                'hierarchical' => false,
                'labels' => [
                    'name' => Resolvers::resolve_field($args, 'label'),
                    'singular_name' => Resolvers::resolve_field($args, 'label'),
                ],
                'show_in_rest' => true,
                'show_ui' => true,
                'rewrite' => false,
            ],
            $args
        );

        if ($options['has_archive']) {
            $args['rewrite'] = [
                'slug' => Utils::string_to_route($args['label']),
                'with_front' => false,
            ];
        }

        if ($options['has_single'] && Resolvers::resolve_field($args['rewrite'], 'slug')) {
            add_filter('akka_taxonomy_singles', function ($taxonomies) use ($taxonomy_slug, $args) {
                if (!isset($taxonomies[$args['rewrite']['slug']])) {
                    $taxonomies[$args['rewrite']['slug']] = array_merge($args, ['slug' => $taxonomy_slug]);
                }
                return $taxonomies;
            });
        }

        if (!$args['label']) {
            throw new \Exception('Akka taxonomy label missing!');
        }

        add_action('init', function () use ($taxonomy_slug, $options, $args) {
            register_taxonomy($taxonomy_slug, $options['post_types'], $args);
        });

        foreach ($options['post_types'] as $post_type) {
            add_filter('akka_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy_slug, $post_type) {
                if (!isset($taxonomy_map[$post_type])) {
                    $taxonomy_map[$post_type] = [];
                }
                if (!in_array($taxonomy_slug, $taxonomy_map[$post_type])) {
                    $taxonomy_map[$post_type][] = $taxonomy_slug;
                }
                return $taxonomy_map;
            });
        }

        foreach ($options['in_archive_post_types'] as $post_type) {
            add_filter('akka_blurb_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy_slug, $post_type) {
                if (!isset($taxonomy_map[$post_type])) {
                    $taxonomy_map[$post_type] = [];
                }
                if (!in_array($taxonomy_slug, $taxonomy_map[$post_type])) {
                    $taxonomy_map[$post_type][] = $taxonomy_slug;
                }
                return $taxonomy_map;
            });
        }

        foreach ($options['acf_field_groups'] as $acf_field_group) {
            $acf_field_group['location'] = Resolvers::resolve_field($acf_field_group, 'location') ?? [
                [
                    [
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => $taxonomy_slug,
                    ],
                ],
            ];
            Acf::register_field_group($acf_field_group);
        }

        if (!is_admin()) {
            return;
        }

        foreach ($options['admin_column_post_types'] as $post_type) {
            add_filter('manage_' . $post_type . '_posts_columns', function ($defaults) use ($taxonomy_slug, $args) {
                $has_date_col = isset($defaults['date']);
                if ($has_date_col) {
                    $date_col = $defaults['date'];
                    unset($defaults['date']);
                }

                $defaults['tax_' . $taxonomy_slug] = $args['label'];

                if ($has_date_col) {
                    $defaults['date'] = $date_col;
                }

                return $defaults;
            });

            add_action(
                'manage_' . $post_type . '_posts_custom_column',
                function ($column_name, $post_id) use ($taxonomy_slug) {
                    if ($column_name == 'tax_' . $taxonomy_slug) {
                        $terms = wp_get_post_terms($post_id, $taxonomy_slug);
                        if (!empty($terms)) {
                            echo implode(
                                ', ',
                                array_map(function ($term) {
                                    return $term->name;
                                }, $terms)
                            );
                        } else {
                            echo '&nbsp;';
                        }
                    }
                },
                10,
                2
            );
        }

        if (count($options['admin_filter_post_types'])) {
            add_action('restrict_manage_posts', function () use ($options, $taxonomy_slug, $args) {
                global $typenow;
                if (in_array($typenow, $options['admin_filter_post_types'])) {
                    wp_dropdown_categories([
                        'show_option_all' => $args['label'],
                        'taxonomy' => $taxonomy_slug,
                        'name' => 'term_' . $taxonomy_slug,
                        'orderby' => 'name',
                        'value_field' => 'slug',
                        'selected' => Resolvers::resolve_field($_GET, 'term_' . $taxonomy_slug),
                        'hierarchical' => false,
                        'depth' => 3,
                        'show_count' => false,
                        'hide_empty' => true,
                    ]);
                }
            });

            add_filter('query_vars', function ($vars) use ($taxonomy_slug) {
                $vars[] .= 'term_' . $taxonomy_slug;

                return $vars;
            });

            // Add params to query
            add_action('pre_get_posts', function ($query) use ($taxonomy_slug) {
                if (!is_admin() || !$query->is_main_query()) {
                    return;
                }

                $tax_query = null;
                if (Resolvers::resolve_field($query->query_vars, 'term_' . $taxonomy_slug)) {
                    $tax_query = [
                        [
                            'taxonomy' => $taxonomy_slug,
                            'field' => 'slug',
                            'terms' => [$query->query_vars['term_' . $taxonomy_slug]],
                        ],
                    ];
                }

                if ($tax_query) {
                    $query->set('tax_query', $tax_query);
                }

                return $query;
            });
        }
    }

    public static function register_taxonomy_for_post_type($taxonomy, $post_type)
    {
        add_action('init', function () use ($taxonomy, $post_type) {
            register_taxonomy_for_object_type($taxonomy, $post_type);
        });
        add_filter('akka_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy, $post_type) {
            if (!isset($taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type] = [];
            }
            if (!in_array($taxonomy, $taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type][] = $taxonomy;
            }
            return $taxonomy_map;
        });
        add_filter('akka_blurb_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy, $post_type) {
            if (!isset($taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type] = [];
            }
            if (!in_array($taxonomy, $taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type][] = $taxonomy;
            }
            return $taxonomy_map;
        });
    }

    public static function unregister_taxonomy_for_post_type($taxonomy, $post_type)
    {
        add_action('init', function () use ($taxonomy, $post_type) {
            unregister_taxonomy_for_object_type($taxonomy, $post_type);
        });
        add_filter('akka_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy, $post_type) {
            if (isset($taxonomy_map[$post_type]) && in_array($taxonomy, $taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type] = array_values(
                    array_filter($taxonomy_map[$post_type], function ($t) use ($taxonomy) {
                        return $t != $taxonomy;
                    })
                );
            }
            return $taxonomy_map;
        });
        add_filter('akka_blurb_post_type_taxonomy_map', function ($taxonomy_map) use ($taxonomy, $post_type) {
            if (isset($taxonomy_map[$post_type]) && in_array($taxonomy, $taxonomy_map[$post_type])) {
                $taxonomy_map[$post_type] = array_values(
                    array_filter($taxonomy_map[$post_type], function ($t) use ($taxonomy) {
                        return $t != $taxonomy;
                    })
                );
            }
            return $taxonomy_map;
        });
    }

    public static function get_single($taxonomy)
    {
        $terms = Term::get_terms($taxonomy['slug'], true);
        $terms_all = Term::get_terms($taxonomy['slug']);

        $taxonomy_single = [
            'post_type' => 'taxonomy',
            'taxonomy' => $taxonomy['slug'],
            'taxonomy_label' => $taxonomy['labels']['singular_name'],
            'slug' => $taxonomy['slug'],
            'url' => '/' . $permalink,
            'post_title' => $taxonomy['labels']['name'],
            'name' => $taxonomy['labels']['name'],
            'terms' => $terms,
            'terms_all' => $terms_all,
        ];

        $taxonomy_single['seo_meta'] = self::get_taxonomy_seo_meta($taxonomy_single);

        return apply_filters('akka_taxonomy_single', $taxonomy_single, $taxonomy);
    }

    private static function get_taxonomy_seo_meta($taxonomy)
    {
        $seo_meta = [
            'seo_title' => $taxonomy['post_title'],
            'canonical_url' => $taxonomy['url'],
        ];
        return apply_filters('akka_taxonomy_seo_meta', $seo_meta, $taxonomy);
    }
}
