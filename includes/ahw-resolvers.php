<?php
use \Akka_headless_wp_content as Content;
use \Akka_headless_wp_utils as Utils;

class Akka_headless_wp_resolvers
{
    public static function resolve_post_base($post)
    {
        return [
            'url' => Utils::parseUrl(get_permalink($post->ID)),
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'slug' => $post->post_name,
        ];
    }

    public static function resolve_post_base_by_post_id($post_id)
    {
        $post = get_post($post_id);
        return self::resolve_post_base($post);
    }

    // post_data_or_fields is either an array with "fields" as key or it is the fields array
    public static function resolve_field($post_data_or_fields, $field_name)
    {
        $fields = null;
        if (!isset($post_data_or_fields[$field_name]) && isset($post_data_or_fields['fields'])) {
            $fields = $post_data_or_fields['fields'];
        } elseif (is_array($post_data_or_fields)) {
            $fields = $post_data_or_fields;
        }
        if (!isset($fields[$field_name]) || empty($fields[$field_name])) {
            return null;
        }
        return $fields[$field_name];
    }

    public static function resolve_boolean_field($post_data_or_fields, $field_name)
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        if (!$field) {
            return false;
        }
        return true;
    }

    public static function resolve_link_field($post_data_or_fields, $field_name)
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        if (!$field) {
            return null;
        }
        return [
            'text' => $field['title'],
            'url' => $field['url'],
            'target' => $field['target'],
        ];
    }

    public static function resolve_array_field($post_data_or_fields, $field_name)
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        if (!$field) {
            return [];
        }
        return $field;
    }

    public static function resolve_post_field($post_data_or_fields, $field_name)
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        if (!$field) {
            return null;
        }
        if (is_numeric($field)) {
            $field = get_post($field);
        }
        return Content::get_post_in_archive($field);
    }

    public static function resolve_posts_field($post_data_or_fields, $field_name)
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        if (!$field) {
            return [];
        }
        return array_map(function ($post_id) {
            $p = get_post($post_id);
            return Content::get_post_in_archive($p);
        }, $field);
    }

    public static function resolve_global_field($field_name)
    {
        return get_field('global_' . $field_name, 'global');
    }

    public static function resolve_image_field($post_data_or_fields, $field_name, $size = 'full')
    {
        $field = self::resolve_field($post_data_or_fields, $field_name);
        return $field ? self::resolve_image($field, $size) : null;
    }

    public static function resolve_wysiwyg_field($post_data_or_fields, $field_name)
    {
        return Utils::parseWysiwyg(self::resolve_field($post_data_or_fields, $field_name));
    }

    public static function resolve_image($image_id, $size = 'full', $include_caption = false)
    {
        if (!$image_id) {
            return null;
        }
        $post_image_attributes = Utils::internal_img_attributes(
            $image_id,
            [
                'size' => $size,
            ],
            $include_caption
        );
        if (empty($post_image_attributes)) {
            return null;
        }
        return $post_image_attributes;
    }

    public static function resolve_image_with_caption($image_id, $size = 'full')
    {
        return self::resolve_image($image_id, $size = 'full', true);
    }

    public static function resolve_post_image($post_id, $size = 'full')
    {
        $post_image_id = get_post_thumbnail_id($post_id);
        return self::resolve_image($post_image_id, $size);
    }
}
