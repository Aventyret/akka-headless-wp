<?php
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_akka_meta_fields as MetaFields;

class Akka_headless_wp_akka_post_types
{
    public static function register_post_type($post_type_slug, $args, $meta_groups = [])
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
        add_action('init', function () use ($post_type_slug, $args) {
            register_post_type($post_type_slug, $args);
        });
        foreach ($meta_groups as $meta_group) {
            MetaFields::register_post_meta_field(
                Resolvers::resolve_array_field($meta_group, 'group'),
                Resolvers::resolve_array_field($meta_group, 'fields'),
                Resolvers::resolve_array_field($meta_group, 'options')
            );
        }
    }
}
