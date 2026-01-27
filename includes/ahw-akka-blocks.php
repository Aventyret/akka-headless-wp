<?php
use \Akka_headless_wp_resolvers as Resolvers;
use \Akka_headless_wp_blocks as Blocks;

class Akka_headless_wp_akka_blocks
{
    private static $akka_blocks = [];

    public static function register_block_type($block_type, $args = [])
    {
        if (!Resolvers::resolve_field($args, 'akka_component_name')) {
            throw new Exception('Missing akka component name for Akka block ' . $block_type);
        }

        add_filter('ahw_allowed_blocks', function ($blocks) use ($block_type, $args) {
            $allow_block = true;
            // If post types are defined, the block is allowed on these post types
            if (Resolvers::resolve_field($args, 'post_types')) {
                $allow_block = in_array(get_post_type(), $args['post_types']);
            }
            if ($allow_block) {
                $blocks = Blocks::add_allowed_blocks($blocks, [$block_type]);
            }
            return $blocks;
        });

        if (is_admin()) {
            return;
        }

        if (!Resolvers::resolve_field($args, 'block_props_callback')) {
            // If no props callback is provided, props are the same as block attributes
            self::register_block_type(
                $block_type,
                array_merge($args, [
                    'block_props_callback' => function ($post_id, $block_attributes) {
                        return $block_attributes;
                    },
                ])
            );
            return;
        }

        self::$akka_blocks[$block_type] = $args;

        add_action('init', function () use ($block_type, $args) {
            register_block_type($block_type, [
                'api_version' => 2,
                'editor_script' => 'editor',
                'render_callback' => function ($block_attributes, $block_content) use ($block_type, $args) {
                    $props = self::get_block_props(get_the_ID(), $block_type, $block_attributes, $block_content);
                    return '<div data-akka-component="' .
                        $args['akka_component_name'] .
                        '" data-akka-props="' .
                        rawurlencode(json_encode($props)) .
                        '"></div>';
                },
            ]);
        });
    }

    public static function register_splx_block_type($block_type, $args = [])
    {
        if (!Resolvers::resolve_field($args, 'akka_component_name')) {
            throw new Exception('Missing akka component name for Solarplexus block ' . $block_type);
        }

        if (!Resolvers::resolve_field($args, 'block_props_callback')) {
            // If no props callback is provided, props are the same as block attributes
            throw new Exception('Missing block props callback for Solarplexus block ' . $block_type);
            return;
        }

        if (Resolvers::resolve_field($args, 'post_types')) {
            // If post types are defined, the block is disallowed on all other post types
            add_filter('ahw_allowed_blocks', function ($blocks) use ($block_type, $args) {
                if (!in_array(get_post_type(), $args['post_types'])) {
                    $blocks = Blocks::remove_unallowed_blocks($blocks, ['splx/' . $block_type]);
                }
                return $blocks;
            });
        }

        add_action('init', function() {
            \Solarplexus_Helpers::use_custom_editor_ssr_component();
        });

        if (is_admin()) {
            return;
        }

        self::$akka_blocks['splx/' . $block_type] = $args;

        add_filter(
            'splx_block_args',
            function ($splx_args, $splx_block_type) use ($block_type, $args) {
                if ($splx_block_type != $block_type) {
                    return $splx_args;
                }
                return array_merge($splx_args, ['props' => $args['block_props_callback'](get_the_ID(), $splx_args)]);
            },
            10,
            2
        );

        add_filter(
            'splx_block_render_callback',
            function ($template, $splx_args, $splx_block_type) use ($block_type, $args) {
                if ($splx_block_type != $block_type) {
                    return $template;
                }
                return '<div data-akka-component="' .
                    $args['akka_component_name'] .
                    '" data-akka-props="' .
                    rawurlencode(json_encode(Resolvers::resolve_array_field($splx_args, 'props'))) .
                    '"></div>';
            },
            10,
            3
        );
    }

    private static function get_block_props($post_id, $block_type, $block_attributes, $block_content = null)
    {
        if (!isset(self::$akka_blocks[$block_type])) {
            throw new Exception('Missing registration for Akka block ' . $block_type);
        }
        $props = $block_attributes;
        // Get props from callback, if one is registered with the block
        if (isset(self::$akka_blocks[$block_type]['block_props_callback'])) {
            $props = self::$akka_blocks[$block_type]['block_props_callback']($post_id, $block_attributes);
        }
        // Add block content as children prop for the frontent
        if ($block_content) {
            $props = array_merge($props, ['children' => $block_content]);
        }
        return $props;
    }

    private static function get_splx_block_props($post_id, $block_type, $block_attributes)
    {
        if (!isset(self::$akka_blocks[$block_type])) {
            throw new Exception('Missing registration for Akka solarplexus block ' . $block_type);
        }
        $props = $block_attributes;
        // Get props from callback, if one is registered with the block
        if (isset(self::$akka_blocks[$block_type]['block_props_callback'])) {
            $block_config = Solarplexus_Helpers::retrieve_block_config(str_replace('splx/', '', $block_type));
            $splx_args = Solarplexus_Helpers::block_args($block_config, $block_attributes);
            $props = self::$akka_blocks[$block_type]['block_props_callback']($post_id, $splx_args);
        }
        return $props;
    }

    private static function get_block_component_name($block_type)
    {
        if (!isset(self::$akka_blocks[$block_type])) {
            throw new Exception('Missing registration for Akka block ' . $block_type);
        }
        return self::$akka_blocks[$block_type]['akka_component_name'];
    }

    public static function render_editor_block($request)
    {
        $data = $request->get_json_params();
        $post_id = Resolvers::resolve_field($data, 'postId');
        $blockType = Resolvers::resolve_field($data, 'blockType');
        $attributes = Resolvers::resolve_array_field($data, 'attributes');
        $akka_component_name = self::get_block_component_name($blockType);
        if (str_starts_with($blockType, 'splx/')) {
            $props = self::get_splx_block_props($post_id, $blockType, $attributes);
        } else {
            $props = self::get_block_props($post_id, $blockType, $attributes);
        }
        $props = array_merge($props, ['isEditor' => true]);
        $block_response = wp_remote_post(AKKA_FRONTEND_INTERNAL_BASE . '/api/editor/component', [
            'method' => 'POST',
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . AKKA_FRONTEND_FLUSH_CACHE_KEY,
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => json_encode([
                'componentName' => $akka_component_name,
                'props' => $props,
            ]),
        ]);
        if (is_wp_error($block_response)) {
            wp_die();
        }

        $block_response_body =
            is_array($block_response) && isset($block_response['body']) ? json_decode($block_response['body']) : false;

        return $block_response_body;
    }
}
