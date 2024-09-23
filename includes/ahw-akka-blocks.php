<?php
use \Akka_headless_wp_resolvers as Resolvers;

class Akka_headless_wp_akka_blocks {
  private static $block_props_callbacks = [];

  public static function register_block_type($block_id, $akka_component_tag, $block_props_callback = NULL) {
    if (is_admin()) {
        return;
    }

    if (!$block_props_callback) {
        // If no props callback is provided, props are the same as block attributes with content as children
        self::register_block_type($block_id, $akka_component_tag, function($block_attributes, $content) {
            return array_merge($block_attributes, ["children" => $content]);
        });
        return;
    }

    self::$block_props_callbacks[$block_id] = $block_props_callback;

    add_action("init", function () use($block_id, $akka_component_tag, $block_props_callback) {
        register_block_type($block_id, [
            "api_version" => 2,
            "editor_script" => "editor",
            "render_callback" => function ($block_attributes, $content) use($block_id, $akka_component_tag, $block_props_callback) {
                $props = $block_props_callback($block_attributes, $content);
                return '<div data-akka-component="' . $akka_component_tag . '" data-akka-props="' .
                    rawurlencode(json_encode($props)) .
                    '"></div>';
            },
        ]);
    });
  }

  public static function get_block_props($block_id, $block_attributes) {
    if (!isset(self::$block_props_callbacks[$block_id])) {
      return NULL;
    }
    return self::$block_props_callbacks[$block_id]($block_attributes);
  }

  public static function render_editor_block($request) {
    $data = $request->get_json_params();
    $blockId = Resolvers::resolve_field(
        $data,
        "blockId"
    );
    $attributes = Resolvers::resolve_field(
        $data,
        "attributes"
    );
    $props = self::get_block_props(
        $blockId,
        $attributes
    );
    $block_response = wp_remote_post(
        AKKA_FRONTEND_INTERNAL_BASE . "/api/editor/block",
        [
            "method" => "POST",
            "timeout" => 10,
            "headers" => [
                "Content-Type" => "application/json; charset=utf-8",
            ],
            "body" => json_encode([
                "blockId" => $blockId,
                "props" => $props,
            ]),
        ]
    );
    if (is_wp_error($block_response)) {
        wp_die();
    }

    $block_response_body =
        is_array($block_response) && isset($block_response["body"])
            ? json_decode($block_response["body"])
            : false;

    return $block_response_body;
  }
}
