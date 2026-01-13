<?php
namespace Akka;

class MetaFields
{
    private static $akka_meta_field_groups = [];

    public static function register_post_meta_field($meta_group, $meta_fields, $options = [])
    {
        $meta_group = array_merge(
            [
                'name' => null,
                'label' => null,
            ],
            $meta_group
        );

        if (!$meta_group['name']) {
            throw new \Exception('Akka meta group name missing!');
        }

        if (!$meta_group['label']) {
            throw new \Exception('Akka meta group label missing!');
        }

        $meta_fields = array_map(function ($meta_field) {
            $meta_field = array_merge(
                [
                    'name' => null,
                    'label' => null,
                    'type' => 'text',
                    'single' => true,
                    'default' => '',
                    'auth_callback' => function () {
                        return current_user_can('edit_posts');
                    },
                ],
                $meta_field
            );

            if (!$meta_field['name']) {
                throw new \Exception('Akka meta field name missing!');
            }

            if (!$meta_field['label']) {
                throw new \Exception('Akka meta field label missing!');
            }

            $meta_type = self::meta_field_type($meta_field['type']);
            if (!in_array($meta_type, ['string', 'boolean', 'integer', 'number', 'array', 'object'])) {
                throw new \Exception('Akka meta field bad field type!');
            }

            if ($meta_field['type'] == 'object' && !Resolvers::resolve_field($meta_field['properties'])) {
                throw new \Exception('Akka meta field missing properties for field type object!');
            }

            return $meta_field;
        }, $meta_fields);

        $options = array_merge(
            [
                'post_types' => ['post'],
                'context' => 'side',
                'priority' => 'default',
            ],
            $options
        );

        foreach ($options['post_types'] as $post_type) {
            // Register meta box
            add_action('add_meta_boxes', function () use ($post_type, $meta_group, $options) {
                add_meta_box(
                    $meta_group['name'],
                    $meta_group['label'],
                    function () use ($meta_group) {
                        echo '<div id="akka_meta_' . $meta_group['name'] . '"></div>';
                    },
                    $post_type,
                    $options['context'],
                    $options['priority']
                );
            });

            // Register post meta
            add_action('init', function () use ($post_type, $meta_fields) {
                foreach ($meta_fields as $meta_field) {
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
                        'type' => self::meta_field_type($meta_field['type']),
                        'single' => $meta_field['single'],
                        'default' => $meta_field['default'], // Note: Setting this to null does NOT work
                        'auth_callback' => $meta_field['auth_callback'],
                    ]);
                }
            });
        }

        self::$akka_meta_field_groups[$meta_group['label']] = [
            'name' => $meta_group['name'],
            'label' => $meta_group['label'],
            'meta_fields' => $meta_fields,
            'options' => $options,
        ];

        add_action('enqueue_block_editor_assets', function () use ($meta_group, $meta_fields) {
            $client_meta_fields = array_map(function ($meta_field) {
                $client_meta_field = $meta_field;
                unset($client_meta_field['auth_callback']);
                return $client_meta_field;
            }, $meta_fields);
            wp_add_inline_script(
                'akka',
                sprintf(
                    "window.akka.registerFieldGroup('%s', '%s');",
                    $meta_group['name'],
                    json_encode($client_meta_fields)
                ),
                'after'
            );
        });
    }

    private static function meta_field_type($akka_field_type)
    {
        if (in_array($akka_field_type, ['text', 'html', 'select'])) {
            return 'string';
        }
        if (in_array($akka_field_type, ['image', 'file'])) {
            return 'integer';
        }
        return $akka_field_type;
    }

    public static function get_post_field($field_name, $p)
    {
        if (is_array($p)) {
            return Resolvers::resolve_field($p, $field_name);
        }
    }
}
