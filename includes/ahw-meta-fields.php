<?php
use \Akka_headless_wp_resolvers as Resolvers;

class Akka_headless_wp_meta_fields
{
    private static $akka_meta_fields = [];

    public static function register_post_meta_field($meta_field, $options = [])
    {
        $meta_field = array_merge(
            [
                'name' => null,
                'type' => 'string',
                'single' => true,
                'default' => '',
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ],
            $meta_field
        );

        if (!$meta_field['name']) {
            throw new Exception('Akka meta field name missing!');
        }

        // TODO: Add image, file, html/rich text and more
        if (!in_array($meta_field['type'], ['string', 'boolean', 'integer', 'number', 'array', 'object'])) {
            throw new Exception('Akka meta field bad field type!');
        }

        if ($meta_field['type'] == 'object' && !Resolvers::resolve_field($meta_field['properties'])) {
            throw new Exception('Akka meta field missing properties for field type object!');
        }

        $options = array_merge(
            [
                'post_types' => ['post'],
                'context' => 'side',
                'priority' => 'default',
            ],
            $options
        );

        foreach ($options['post_types'] as $post_type) {
            // Register boat model meta box
            add_action('add_meta_boxes', function () use ($post_type, $meta_field, $options) {
                add_meta_box(
                    $meta_field['name'],
                    $meta_field['label'],
                    function () {
                        echo '<div id="boat-model-meta"></div>';
                    },
                    $post_type,
                    $options['context'],
                    $options['priority']
                );
            });

            // Register boat model post meta
            add_action('init', function () use ($post_type) {
                register_post_meta($post_type, '_' . $meta_field['name'], [
                    'show_in_rest' =>
                        $meta_field['type'] == 'object'
                            ? [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $meta_field['properties'],
                                ],
                            ]
                            : true,
                    'type' => $meta_field['type'],
                    'single' => $meta_field['single'],
                    'default' => $meta_field['default'], // Note: Setting this to null does NOT work
                    'auth_callback' => $meta_field['auth_callback'],
                ]);
            });
        }

        self::$akka_meta_fields[] = [
            'meta_field' => $meta_field,
            'options' => $options,
        ];
    }
}
