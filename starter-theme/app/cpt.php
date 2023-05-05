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

    register_post_type("testimonial", [
        "label" => esc_html__("Testimonial", "sage"),
        "has_archive" => false,
        "public" => true,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-businessperson",
        "hierarchical" => false,
        "supports" => ["title", "revisions", "thumbnail"],
        "show_in_rest" => true,
        "menu_position" => 11,
        "labels" => [
            "name" => esc_html__("Testimonials", "sage"),
            "singular_name" => esc_html__("Testimonial", "sage"),
        ],
    ]);

    register_post_type("business_area", [
        "label" => esc_html__("Business areas", "sage"),
        "has_archive" => true,
        "public" => true,
        "publicly_queryable" => true,
        "exclude_from_search" => false,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-money-alt",
        "hierarchical" => false,
        "rewrite" => [
            "slug" => Aventyret_headless_wp_utils::stringToRoute(
                __("Business areas", "sage")
            ),
        ],
        "supports" => ["title", "editor", "revisions", "excerpt", "thumbnail"],
        "taxonomies" => ["category", "post_tag"],
        "show_in_rest" => true,
        "menu_position" => 5,
        "labels" => [
            "name" => esc_html__("Business areas", "sage"),
            "singular_name" => esc_html__("Business area", "sage"),
        ],
    ]);

    register_post_type("product", [
        "label" => esc_html__("Products", "sage"),
        "has_archive" => true,
        "public" => true,
        "publicly_queryable" => true,
        "exclude_from_search" => false,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-products",
        "hierarchical" => false,
        "rewrite" => [
            "slug" => Aventyret_headless_wp_utils::stringToRoute(
                __("Products", "sage")
            ),
        ],
        "supports" => ["title", "editor", "revisions", "excerpt", "thumbnail"],
        "taxonomies" => ["category", "post_tag"],
        "show_in_rest" => true,
        "menu_position" => 6,
        "labels" => [
            "name" => esc_html__("Products", "sage"),
            "singular_name" => esc_html__("Product", "sage"),
        ],
    ]);

    register_post_type("case", [
        "label" => esc_html__("Projects", "sage"),
        "has_archive" => true,
        "public" => true,
        "publicly_queryable" => true,
        "exclude_from_search" => false,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-format-status",
        "hierarchical" => false,
        "rewrite" => [
            "slug" => Aventyret_headless_wp_utils::stringToRoute(
                __("Projects", "sage")
            ),
        ],
        "supports" => ["title", "editor", "revisions", "excerpt", "thumbnail"],
        "taxonomies" => ["category", "post_tag"],
        "show_in_rest" => true,
        "menu_position" => 7,
        "labels" => [
            "name" => esc_html__("Projects", "sage"),
            "singular_name" => esc_html__("Project", "sage"),
        ],
    ]);

    register_post_type("takeover", [
        "label" => esc_html__("Takeover", "sage"),
        "has_archive" => false,
        "public" => true,
        "publicly_queryable" => true,
        "exclude_from_search" => false,
        "show_ui" => true,
        "show_in_nav_menus" => true,
        "menu_icon" => "dashicons-align-full-width",
        "hierarchical" => false,
        "supports" => ["title", "editor", "revisions"],
        "taxonomies" => [],
        "show_in_rest" => true,
        "menu_position" => 8,
        "labels" => [
            "name" => esc_html__("Takeover", "sage"),
            "singular_name" => esc_html__("Takeover", "sage"),
        ],
    ]);
});
