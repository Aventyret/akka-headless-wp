<?php
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_akka_meta_fields as MetaFields;
use \Akka_headless_wp_blocks as Blocks;
use \Akka_headless_wp_acf as Acf;

class Akka_headless_wp_akka_post_types
{
    public static function register_post_type($post_type_slug, $args, $options = [])
    {
        $args = array_merge(
            [
                'label' => null,
                'has_archive' => false,
                'public' => false,
                'exclude_from_search' => false,
                'show_ui' => true,
                'show_in_nav_menus' => true,
                'menu_icon' => 'dashicons-admin-post',
                'hierarchical' => false,
                'supports' => ['title', 'revisions', 'thumbnail', 'editor', 'custom-fields'],
                'show_in_rest' => true,
                'menu_position' => 10,
                'labels' => [
                    'name' => Resolvers::resolve_field($args, 'label'),
                    'singular_name' => Resolvers::resolve_field($args, 'label'),
                ],
            ],
            $args
        );
        if (!$args['label']) {
            throw new Exception('Akka post type label missing!');
        }
        $options = array_merge(
            [
                'meta_groups' => [],
                'acf_field_groups' => [],
                'allowed_core_blocks' => [],
                'unallowed_core_blocks' => [],
            ],
            $options
        );
        add_action('init', function () use ($post_type_slug, $args) {
            register_post_type($post_type_slug, $args);
        });
        if ($args['has_archive']) {
            add_filter('ahw_post_types_with_archives', function ($post_types) {
                if (!in_array($post_type_slug, $post_types)) {
                    $post_types[] = $post_type_slug;
                }
                return $post_types;
            });
        }
        foreach ($options['meta_groups'] as $meta_group) {
            MetaFields::register_post_meta_field(
                Resolvers::resolve_array_field($meta_group, 'group'),
                Resolvers::resolve_array_field($meta_group, 'fields'),
                array_merge(
                    [
                        'post_types' => [$post_type_slug],
                    ],
                    Resolvers::resolve_array_field($meta_group, 'options')
                )
            );
        }
        foreach ($options['acf_field_groups'] as $acf_field_group) {
            $acf_field_group['location'] = Resolvers::resolve_field($acf_field_group, 'location') ?? [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => $post_type_slug,
                    ],
                ],
            ];
            Acf::register_field_group($acf_field_group);
        }
        if (!empty($options['allowed_core_blocks'])) {
            add_filter(
                'ahw_allowed_blocks',
                function ($blocks) use ($post_type_slug, $options) {
                    if (in_array(get_post_type(), $args['post_types'])) {
                        $blocks = Blocks::add_allowed_blocks($blocks, $options['allowed_core_blocks']);
                    }
                    return $blocks;
                },
                11
            );
        }
        if (!empty($options['unallowed_core_blocks'])) {
            add_filter(
                'ahw_allowed_blocks',
                function ($blocks) use ($post_type_slug, $options) {
                    if (!in_array(get_post_type(), $args['post_types'])) {
                        $blocks = Blocks::remove_unallowed_blocks($blocks, $options['unallowed_core_blocks']);
                    }
                    return $blocks;
                },
                11
            );
        }
    }
}
