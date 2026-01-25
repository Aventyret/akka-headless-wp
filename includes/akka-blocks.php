<?php
namespace Akka;

class Blocks
{
    public static function render_block($parsed_block, $block)
    {
        if (!Utils::is_headless()) {
            return $parsed_block;
        }
        if ($block['blockName'] == 'core/embed') {
            $parsed_block =
                '
      <div class="wp-embed-responsive">
        ' .
                $parsed_block .
                '
      </div>';
        }
        return $parsed_block;
    }

    public static function allowed_blocks($block_editor_context, $editor_context)
    {
        if (!empty($editor_context->post)) {
            $allowed_blocks = [
                'core/paragraph',
                'core/image',
                'core/heading',
                // 'core/gallery',
                'core/list',
                'core/list-item',
                'core/quote',
                // 'core/audio',
                // 'core/file',
                // 'core/video',
                // 'core/group',
                // 'core/columns',
                // 'core/navigation',
                'core/embed',
                // 'core/table',
                // 'core/verse',
                // 'core/code',
                // 'core/freeform',
                // 'core/html',
                // 'core/preformatted',
                // 'core/pullquote',
                'core/button',
                'core/buttons',
                // 'core/text-columns',
                // 'core/media-text',
                // 'core/more',
                // 'core/nextpage',
                // 'core/separator',
                // 'core/spacer',
                // 'core/shortcode',
                // 'core/archives',
                // 'core/categories',
                // 'core/latest-comments',
                // 'core/latest-posts',
                // 'core/calendar',
                // 'core/rss',
                // 'core/search',
                // 'core/tag-cloud',
                // 'gravityforms/form',
            ];
            $allowed_blocks = array_merge($allowed_blocks, self::splx_block_ids());
            return array_values(apply_filters('akka_allowed_blocks', $allowed_blocks));
        }
        return $block_editor_context;
    }

    public static function splx_block_ids()
    {
        if (!class_exists('Solarplexus_Helpers')) {
            return [];
        }
        return array_map(function ($block_config) {
            return 'splx/' . $block_config['id'];
        }, \Solarplexus_Helpers::retrieve_block_configs());
    }

    public static function get_h2_blocks($content, $level = null)
    {
        $heading_blocks = [];

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);

        foreach ($dom->getElementsByTagName('h2') as $h2) {
            $heading_blocks[] = $h2->textContent;
        }

        return $heading_blocks;
    }

    public static function add_allowed_blocks($blocks, $allowed_blocks)
    {
        $blocks = array_merge($blocks, $allowed_blocks);
        return array_values($blocks);
    }

    public static function remove_unallowed_blocks($blocks, $unallowed_blocks)
    {
        $blocks = array_filter($blocks, function ($block) use ($unallowed_blocks) {
            return !in_array($block, $unallowed_blocks);
        });
        return array_values($blocks);
    }

    public static function register_core_block_style($block, $style)
    {
        add_action('enqueue_block_editor_assets', function () use ($block, $style) {
            $data =
                '
            window.akka.coreBlockStyles = window.akka.coreBlockStyles || [];
            window.akka.coreBlockStyles.push(' .
                sprintf('{block: "%s", style: "%s"}', $block, $style) .
                ');
            ';
            wp_add_inline_script('akka', $data, 'after');
        });
    }

    public static function register_core_block_variation($block, $variation)
    {
        add_action('enqueue_block_editor_assets', function () use ($block, $variation) {
            $data =
                '
            window.akka.coreBlockVariations = window.akka.coreBlockVariations || [];
            window.akka.coreBlockVariations.push(' .
                sprintf('{block: "%s", variation: "%s"}', $block, $variation) .
                ');
            ';
            wp_add_inline_script('akka', $data, 'after');
        });
    }
}
