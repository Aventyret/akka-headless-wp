<?php
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_akka_meta_fields as MetaFields;

class Akka_headless_wp_acf
{
    public static function register_field_group($field_group)
    {
        if (function_exists('acf_add_local_field_group')) {
            add_action('acf/init', function () use ($field_group) {
                if (!Resolvers::resolve_field($field_group, 'key')) {
                    throw new Exception('Akka acf field group: key is missing!');
                }
                if (!Resolvers::resolve_field($field_group, 'title')) {
                    throw new Exception('Akka acf field group: title is missing!');
                }
                if (!Resolvers::resolve_field($field_group, 'fields')) {
                    throw new Exception('Akka acf field group: fields is missing!');
                }
                if (!Resolvers::resolve_field($field_group, 'location')) {
                    throw new Exception('Akka acf field group: location is missing!');
                }
                $field_group['fields'] = self::set_field_keys($field_group['fields']);
                $field_group = array_merge(
                    [
                        'menu_order' => 2,
                        'position' => 'side',
                        'active' => true,
                        'show_in_rest' => 0,
                    ],
                    $field_group
                );
                acf_add_local_field_group($field_group);
            });
        }
    }

    private static function set_field_keys($fields)
    {
        foreach ($fields as $index => $field) {
            if (!Resolvers::resolve_field($field, 'name')) {
                error_log(json_encode($field));
                throw new Exception('Akka acf field: name is missing!');
            }
            if (!Resolvers::resolve_field($field, 'label')) {
                error_log(json_encode($field));
                throw new Exception('Akka acf field: label is missing!');
            }
            $fields[$index]['key'] =
                Resolvers::resolve_field($field, 'key') ?? '_' . Resolvers::resolve_field($field, 'name');
            if (!empty(Resolvers::resolve_array_field($field, 'fields'))) {
                $fields[$index]['fields'] = self::set_field_keys($field['fields']);
            }
        }
        return $fields;
    }
}
