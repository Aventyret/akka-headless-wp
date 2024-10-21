<?php

add_action('template_redirect', 'Akka_headless_wp_utils::redirect_to_frontend');

add_action('acf/init', 'akka_headless_wp_create_acf_fields');

add_action('render_block', 'Akka_headless_wp_blocks::render_block', 10, 2);

// Disable srcset on images
add_filter('max_srcset_image_width', function() { return 1; });

add_filter('allowed_block_types_all', 'Akka_headless_wp_blocks::allowed_blocks', 10, 2);

add_action('the_content', 'Akka_headless_wp_utils::replaceHrefs');

add_action('the_content', 'Akka_headless_wp_utils::replaceSrcs');

add_action('the_content', 'Akka_headless_wp_utils::replaceHtmlCharachters');

add_action('init', 'Akka_headless_wp_utils::check_cms_cookie');

add_action('wp_login', 'Akka_headless_wp_utils::set_cms_cookie');

add_action('wp_logout', 'Akka_headless_wp_utils::remove_cms_cookie');

add_action('enqueue_block_editor_assets', 'Akka_headless_wp_utils::enqueue_frontend_styles');

add_action('enqueue_block_editor_assets', 'Akka_headless_wp_utils::enqueue_editor_assets');

add_action('save_post','Akka_headless_wp_utils::flush_frontend_cache');

add_action('acf/save_post','Akka_headless_wp_utils::flush_frontend_cache');
