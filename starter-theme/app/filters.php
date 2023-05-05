<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter("excerpt_more", function () {
    return sprintf(
        ' &hellip; <a href="%s">%s</a>',
        get_permalink(),
        __("Continued", "sage")
    );
});

/**
 * Allow svg uploads.
 *
 * @return array
 */
add_filter("upload_mimes", function ($mimes) {
    $mimes["svg"] = "image/svg+xml";
    return $mimes;
});

/**
 * Add section block to editor for new posts
 *
 * @return string
 */
foreach (["post", "page", "case", "takeover"] as $post_type) {
    add_filter(
        "rest_prepare_" . $post_type,
        function ($data, $post) {
            if ($post->post_status === "auto-draft" && !$post->post_content) {
                $data->data["content"]["raw"] = '<!-- wp:aventyret/section -->
     <section class="wp-block-aventyret-section section section--color-default section--has-padding-top section--has-padding-bottom">
     </section>
     <!-- /wp:aventyret/section -->';
            }
            return $data;
        },
        10,
        2
    );
}

/**
 * Wrap image blocks in Glorified Lists in <li> elements
 * But skip image blocks inside glorified list item blocks
 */
add_filter(
    "render_block",
    function ($block_content, $block) {
        $output = $block_content;
        $block_name = empty($block["blockName"])
            ? "default"
            : str_replace("/", "-", $block["blockName"]);

        if ("aventyret/glorified-list" === $block["blockName"]) {
            $block_content = str_replace(
                "<figure",
                '<li><div class="Glorified-list__items-row-image"><figure',
                $block_content
            );
            $block_content = str_replace(
                "</figure>",
                "</figure></div></li>",
                $block_content
            );
            $block_content = str_replace(
                '<li><div class="Glorified-list__items-row-image"><li><div class="Glorified-list__items-row-image"><figure',
                "<figure",
                $block_content
            );
            $block_content = str_replace(
                "</figure></div></li></div></li>",
                "</figure>",
                $block_content
            );
        }

        if ("aventyret/glorified-list-item" === $block["blockName"]) {
            $block_content = str_replace(
                "<figure",
                '<li><div class="Glorified-list__items-row-image"><figure',
                $block_content
            );
            $block_content = str_replace(
                "</figure>",
                "</figure></div></li>",
                $block_content
            );
        }

        return $block_content;
    },
    10,
    2
);

/**
 * Here we adjust content on custom post type archives
 *
 * @return string
 */
add_filter("ahw_post_type_data", function ($post_type_data) {
    if (
        $post_type_data["post_type"] != "post" &&
        !isset($post_type_data["masthead"])
    ) {
        $post_type_data["masthead"] = [
            "is_rich_media" => false,
            "title" => $post_type_data["name"],
        ];
    }
    if (!isset($post_type_data["post_footer"])) {
        $post_type_data["post_footer"] = Aventyret_content::get_post_footer([]);
    }
    return $post_type_data;
});

/**
 * Here we adjust content on taxonomy term archives
 *
 * @return string
 */
add_filter("ahw_taxonomy_term_data", function ($taxonomy_term_data) {
    if (!isset($taxonomy_term_data["masthead"])) {
        $taxonomy_term_data["masthead"] = [
            "is_rich_media" => false,
            "title" => $taxonomy_term_data["name"],
        ];
    }
    if (!isset($taxonomy_term_data["post_footer"])) {
        $taxonomy_term_data["post_footer"] = Aventyret_content::get_post_footer(
            []
        );
    }
    return $taxonomy_term_data;
});

/**
 * Here we adjust what custom post types should have archives
 *
 * @return string
 */
add_filter("ahw_post_types_with_archives", function ($post_types) {
    $post_types[] = "case";
    return $post_types;
});

/**
 * Here we adjust site meta
 *
 * @return string
 */
add_filter("ahw_site_meta", function ($site_meta) {
    $site_meta = Aventyret_content::get_site_meta_header($site_meta);
    $site_meta = Aventyret_content::get_site_meta_cookies($site_meta);
    $site_meta = Aventyret_content::get_site_meta_footer($site_meta);
    return $site_meta;
});

/**
 * Here we adjust how posts are displayed in archives
 *
 * @return string
 */
add_filter(
    "awh_post_in_archive",
    function ($post_in_archive, $post) {
        $post_prefix =
            '<span class="Blurb__date">' .
            get_the_date("Y-m-d", $post->ID) .
            "</span> ";
        if (in_array($post->post_type, ["page", "case"])) {
            $post_categories = get_the_category($post->ID);
            $post_prefix = !empty($post_categories)
                ? '<span class="Blurb__taxonomy">' .
                    $post_categories[0]->name .
                    "</span> "
                : "";
        }
        $post_in_archive["description"] = $post_prefix . $post->post_excerpt;
        $image_icon_src = get_field("image_icon", $post->ID);
        $post_in_archive["image_icon_src"] = $image_icon_src
            ? $image_icon_src
            : null;
        return $post_in_archive;
    },
    10,
    2
);

/**
 * Here we adjust the headless plugins enabled blocks
 *
 * @return string
 */
add_filter("ahw_allowed_blocks", function ($allowed_blocks) {
    $allowed_blocks[] = "aventyret/section";
    $allowed_blocks[] = "aventyret/glorified-list";
    $allowed_blocks[] = "aventyret/glorified-list-item";
    $allowed_blocks[] = "aventyret/image-and-text";
    $allowed_blocks[] = "aventyret/infobox";
    $allowed_blocks[] = "aventyret/icon-list";
    $allowed_blocks[] = "aventyret/takeover";
    $allowed_blocks[] = "aventyret/blurbs-horizontal";
    $allowed_blocks[] = "aventyret/blurb";
    return $allowed_blocks;
});

/**
 * Here we adjust the data for headless get post
 *
 * @return array
 */
add_filter("ahw_post_data", function ($post_data) {
    if (!class_exists("\App\Aventyret_content")) {
        return $post_data;
    }
    $fields = $post_data["fields"];
    if (isset($fields) && isset($fields["masthead_title"])) {
        $post_data["masthead"] = Aventyret_content::get_masthead(
            $fields,
            $post_data["post_title"],
            $post_data["post_content"],
            $post_data["featured_image"]
        );
    }
    if (isset($fields) && isset($fields["lead_has_lead"])) {
        $post_data["lead"] = Aventyret_content::get_lead($fields);
    }
    if (isset($fields) && isset($fields["related_has_related"])) {
        $post_data["related"] = Aventyret_content::get_related(
            $fields,
            $post_data["post_id"]
        );
    }
    if ($post_data["page_template"] == "contact") {
        $post_data = array_merge(
            $post_data,
            Aventyret_content::get_contact_page_data($fields)
        );
    }
    if ($post_data["post_id"] === get_option("page_on_front")) {
        $post_data = array_merge(
            $post_data,
            Aventyret_content::get_front_page_data($fields)
        );
    }
    $post_data["post_footer"] = Aventyret_content::get_post_footer($fields);
    return $post_data;
});

add_action("the_content", function ($content) {
    $content = Aventyret_content::parse_takeover_blocks($content);
    return $content;
});
