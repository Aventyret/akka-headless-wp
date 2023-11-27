<?php
use \Akka_headless_wp_content as Content;

class Akka_headless_wp_resolvers {
  public static function resolve_post_base($post) {
    return [
      "url" => \Akka_headless_wp_utils::parseUrl(
          get_permalink($post->ID)
      ),
      "post_id" => $post->ID,
      "post_title" => $post->post_title,
      "post_type" => $post->post_type,
    ];
  }

  public static function resolve_post_base_by_post_id($post_id) {
    $post = get_post($post_id);
    return self::resolve_post_base($post);
  }

  // post_data_or_fields is either an array with "fields" as key or it is the fields array
  public static function resolve_field($post_data_or_fields, $field_name) {
    $fields = NULL;
    if (isset($post_data_or_fields["fields"])) {
      $fields = $post_data_or_fields["fields"];
    } else if (is_array($post_data_or_fields)) {
      $fields = $post_data_or_fields;
    }
    if (!isset($fields[$field_name]) || empty($fields[$field_name])) {
      return NULL;
    }
    return $fields[$field_name];
  }

  public static function resolve_boolean_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    if (!$field) {
      return FALSE;
    }
    return TRUE;
  }

  public static function resolve_link_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    if (!$field) {
      return NULL;
    }
    return [
      "text" => $field["title"],
      "url" => $field["url"],
      "target" => $field["target"],
    ];
  }

  public static function resolve_array_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    if (!$field) {
      return [];
    }
    return $field;
  }

  public static function resolve_post_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    if (!$field) {
      return NULL;
    }
    if (is_numeric($field)) {
      $field = get_post($field);
    }
    return Content::get_post_in_archive($field);
  }

  public static function resolve_posts_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    if (!$field) {
      return [];
    }
    return array_map(
        function ($post_id) {
            $p = get_post($post_id);
            return Content::get_post_in_archive($p);
        },
        $field
    );
  }

  public static function resolve_global_field($field_name) {
    return get_field(
        "global_" . $field_name,
        "global"
    );
  }

  public static function resolve_image_field($post_data_or_fields, $field_name) {
    $field = self::resolve_field($post_data_or_fields, $field_name);
    return $field ? self::resolve_image($field) : NULL;
  }

  public static function resolve_wysiwyg_field($post_data_or_fields, $field_name) {
    return \Akka_headless_wp_utils::parseWysiwyg(self::resolve_field($post_data_or_fields, $field_name));
  }

  public static function resolve_image($image_id, $size = "full") {
    if (!$image_id) {
      return NULL;
    }
    $post_image_attributes = \Akka_headless_wp_utils::internal_img_attributes($image_id,
      [
          "size" => $size,
      ]
    );
    if (empty($post_image_attributes)) {
        return NULL;
    }
    return $post_image_attributes;
  }

  public static function resolve_post_image($post_id, $size = "full") {
    $post_image_id = get_post_thumbnail_id($post_id);
    return self::resolve_image($post_image_id, $size);
  }
}
