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
    }
}
