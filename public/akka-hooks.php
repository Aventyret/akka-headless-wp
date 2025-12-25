<?php

add_action('template_redirect', 'Akka\Utils::redirect_to_frontend');

add_action('render_block', 'Akka\Blocks::render_block', 10, 2);

// Disable srcset on images
add_filter('max_srcset_image_width', function () {
    return 1;
});

add_filter('allowed_block_types_all', 'Akka\Blocks::allowed_blocks', 10, 2);

add_action('the_content', 'Akka\Utils::replaceHrefs');

add_action('the_content', 'Akka\Utils::replaceSrcs');

add_action('the_content', 'Akka\Utils::replaceHtmlCharachters');

add_action('init', 'Akka\Utils::check_cms_cookie');

add_action('wp_login', 'Akka\Utils::set_cms_cookie');

add_action('wp_logout', 'Akka\Utils::remove_cms_cookie');

add_action('enqueue_block_editor_assets', 'Akka\Utils::enqueue_frontend_styles');

add_action('enqueue_block_editor_assets', 'Akka\Utils::enqueue_editor_assets');

add_action('save_post', 'Akka\Utils::flush_frontend_cache');

add_action('acf/save_post', 'Akka\Utils::flush_frontend_cache');
