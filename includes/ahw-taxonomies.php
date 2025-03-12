<?php
use \Akka_headless_wp_utils as Utils;
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_akka_meta_fields as MetaFields;
use \Akka_headless_wp_blocks as Blocks;
use \Akka_headless_wp_acf as Acf;

class Akka_headless_wp_akka_taxonomies
{
    public static function register_taxonomy($taxonomy_slug, $args, $options = [])
    {
        $options = array_merge(
            [
                'post_types' => ['post'],
                'in_archive_post_types' => [],
                'admin_column_post_types' => [],
                'has_archive' => false,
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
                'slug' => Utils::stringToRoute($args['label']),
                'with_front' => false,
            ];
        }

        if (!$args['label']) {
            throw new Exception('Akka taxonomy label missing!');
        }

        add_action('init', function () use ($taxonomy_slug, $options, $args) {
            register_taxonomy($taxonomy_slug, $options['post_types'], $args);
        });

        foreach ($options['post_types'] as $post_type) {
            add_filter('ahw_headless_post_type_taxonomy_map', function ($taxonomy_map) use (
                $taxonomy_slug,
                $post_type
            ) {
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
            add_filter('ahw_headless_in_archive_post_type_taxonomy_map', function ($taxonomy_map) use (
                $taxonomy_slug,
                $post_type
            ) {
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
    }
}
