<?php
acf_add_options_page([
    "page_title" => __("Site Content", "sage"),
    "menu_title" => __("Site Content", "sage"),
    "menu_slug" => "global",
    "redirect" => true,
    "icon_url" => "dashicons-admin-site-alt",
    "position" => 60,
]);

acf_add_options_sub_page([
    "title" => __("Global Header", "sage"),
    "menu" => __("Global Header", "sage"),
    "menu_slug" => "global-header",
    "post_id" => "global",
    "parent" => "global",
    "capability" => "edit_posts",
    "position" => "0",
    "autoload" => true,
]);

acf_add_options_sub_page([
    "title" => __("Cookie consent", "sage"),
    "menu" => __("Cookie consent", "sage"),
    "menu_slug" => "global-cookies",
    "post_id" => "global",
    "parent" => "global",
    "capability" => "edit_posts",
    "position" => "2",
    "autoload" => true,
]);

acf_add_options_sub_page([
    "title" => __("Global Footer", "sage"),
    "menu" => __("Global Footer", "sage"),
    "menu_slug" => "global-footer",
    "post_id" => "global",
    "parent" => "global",
    "capability" => "edit_posts",
    "position" => "4",
    "autoload" => true,
]);
