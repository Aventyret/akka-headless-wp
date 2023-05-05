<?php

add_action("init", function () {
    register_post_type("person", [
        "label" => esc_html__("People", "sage"),
        "has_archive" => false,
        "public" => true,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-admin-users",
        "hierarchical" => false,
        "supports" => ["title", "revisions", "thumbnail"],
        "show_in_rest" => true,
        "menu_position" => 10,
        "labels" => [
            "name" => esc_html__("People", "sage"),
            "singular_name" => esc_html__("People", "sage"),
        ],
    ]);
});
