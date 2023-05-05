<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\bundle;
/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action(
    "enqueue_block_editor_assets",
    function () {
        bundle("editor")->enqueue();
    },
    100
);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action(
    "after_setup_theme",
    function () {
        /**
         * Enable features from the Soil plugin if activated.
         *
         * @link https://roots.io/plugins/soil/
         */
        add_theme_support("soil", [
            "clean-up",
            "nav-walker",
            "nice-search",
            "relative-urls",
        ]);

        /**
         * Disable full-site editing support.
         *
         * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
         */
        remove_theme_support("block-templates");

        /**
         * Register the navigation menus.
         *
         * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
         */
        register_nav_menus([
            "primary_navigation" => __("Primary Navigation", "sage"),
            "footer_navigation" => __("Footer Navigation", "sage"),
        ]);

        /**
         * Disable the default block patterns.
         *
         * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
         */
        remove_theme_support("core-block-patterns");

        /**
         * Set image alignment.
         */
        add_theme_support("align-wide");
        remove_theme_support("align-left");
        remove_theme_support("align-right");

        /**
         * Enable plugins to manage the document title.
         *
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
         */
        add_theme_support("title-tag");

        /**
         * Enable post thumbnail support.
         *
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support("post-thumbnails");

        /**
         * Enable responsive embed support.
         *
         * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#responsive-embedded-content
         */
        add_theme_support("responsive-embeds");

        /**
         * Enable HTML5 markup support.
         *
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
         */
        add_theme_support("html5", [
            "caption",
            "comment-form",
            "comment-list",
            "gallery",
            "search-form",
            "script",
            "style",
        ]);

        /**
         * Enable selective refresh for widgets in customizer.
         *
         * @link https://developer.wordpress.org/themes/advanced-topics/customizer-api/#theme-support-in-sidebars
         */
        add_theme_support("customize-selective-refresh-widgets");

        /**
         * Disable colors.
         *
         * @link https://richtabor.com/disable-gutenberg-colors/
         */
        add_theme_support("editor-font-sizes", []);

        /**
         * Square image format.
         */
        add_image_size("square", 1000, 1000, ["center", "center"]);

        /**
         * Add translations text fomain
         */
        load_theme_textdomain(
            "sage",
            get_stylesheet_directory() . "/resources/lang"
        );
    },
    20
);
