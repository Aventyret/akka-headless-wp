<?php

class Akka_headless_wp_blocks
{
    public static function render_block($parsed_block, $block)
    {
        if (!Akka_headless_wp_utils::isHeadless()) {
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
            return array_values(apply_filters('ahw_allowed_blocks', $allowed_blocks));
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
        }, Solarplexus_Helpers::retrieve_block_configs());
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
}
