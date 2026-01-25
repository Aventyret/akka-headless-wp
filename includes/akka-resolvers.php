<?php
namespace Akka;

class Resolvers
{
    public static function resolve_post_base($post)
    {
        return [
            'url' => Post::get_url($post->ID),
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

    // NOTE $fields_source is either an array with "fields" as key or it is the fields array itself
    public static function resolve_field($fields_source, $field_name)
    {
        $fields = null;
        if (!isset($fields_source[$field_name]) && isset($fields_source['fields'])) {
            $fields = $fields_source['fields'];
        } elseif (is_array($fields_source)) {
            $fields = $fields_source;
        }
        // If this is a post object and fields is not set – try to get field from database
        if (
            !isset($fields[$field_name]) &&
            isset($fields_source['post_type']) &&
            isset($fields_source['post_id']) &&
            !isset($fields_source['fields'])
        ) {
            return get_field($field_name, $fields_source['post_id']);
        }
        if (!isset($fields[$field_name]) || empty($fields[$field_name])) {
            return null;
        }
        return $fields[$field_name];
    }

    public static function resolve_boolean_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return false;
        }
        return true;
    }

    public static function resolve_link_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return null;
        }
        return [
            'text' => $field['title'],
            'url' => Utils::parseUrl($field['url']),
            'target' => $field['target'],
        ];
    }

    public static function resolve_array_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return [];
        }
        return $field;
    }

    public static function resolve_post_blurb_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return null;
        }
        if (is_numeric($field)) {
            $field = get_post($field);
        }
        return Post::post_to_blurb($field);
    }

    public static function resolve_post_blurbs_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return [];
        }
        return array_map(function ($post_id) {
            if (is_numeric($field)) {
                $p = get_post($p);
            }
            return Post::post_to_blurb($p);
        }, $field);
    }

    public static function resolve_post_single_field($fields_source, $field_name)
    {
        $field = self::resolve_field($fields_source, $field_name);
        if (!$field) {
            return null;
        }
        return Post::get_single($field);
    }

    public static function resolve_global_field($field_name)
    {
        return get_field('global_' . $field_name, 'global');
    }

    public static function resolve_image_field($fields_source, $field_name, $size = 'full')
    {
        $field = self::resolve_field($fields_source, $field_name);
        if ($field && is_array($field) && isset($field['ID'])) {
            $field = $field['ID'];
        }
        return $field ? self::resolve_image($field, $size) : null;
    }

    public static function resolve_wysiwyg_field($fields_source, $field_name)
    {
        return Utils::parseWysiwyg(self::resolve_field($fields_source, $field_name));
    }

    public static function resolve_audio_or_video($media_id)
    {
        if (!$media_id) {
            return null;
        }
        $post_media_attributes = Utils::internal_audio_and_video_attributes($media_id);
        if (empty($post_media_attributes)) {
            return null;
        }
        return $post_media_attributes;
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
