<?php

namespace App;

class Aventyret_content
{
    public static function get_masthead(
        $fields,
        $post_title,
        $post_content,
        $featured_image
    ) {
        $bg_color =
            $fields["masthead_bg_color"] && $fields["masthead_media"] === ""
                ? $fields["masthead_bg_color"]
                : null;
        $bg_image = null;
        $bg_video = null;

        if (
            $fields["masthead_media"] === "featured" &&
            !empty($featured_image)
        ) {
            $bg_image = $featured_image
                ? [
                    "src" => $featured_image["src"],
                    "width" => $featured_image["width"],
                    "height" => $featured_image["height"],
                ]
                : null;
        }
        if ($fields["masthead_media"] === "image") {
            $image_attributes = \Aventyret_headless_wp_utils::internal_img_attributes(
                $fields["masthead_bg_image"]
            );
            $bg_image = !empty($image_attributes)
                ? [
                    "src" => $image_attributes["src"],
                    "width" => $image_attributes["width"],
                    "height" => $image_attributes["height"],
                ]
                : null;
        }
        if ($fields["masthead_media"] === "video") {
            $video_src = \Aventyret_headless_wp_utils::internal_img_attributes(
                $fields["masthead_bg_video"]
            );
            $video_metadata = wp_get_attachment_metadata(
                $fields["masthead_bg_video"]
            );
            $bg_video = $video_metadata
                ? [
                    "src" => wp_get_attachment_url(
                        $fields["masthead_bg_video"]
                    ),
                    "width" => $video_metadata["width"],
                    "height" => $video_metadata["height"],
                ]
                : null;
        }
        $masthead = [
            "is_rich_media" => $bg_color || $bg_image || $bg_video,
            "title" => $fields["masthead_title"]
                ? $fields["masthead_title"]
                : $post_title,
            "text" => $fields["masthead_text"],
            "bg_color" => $bg_color,
            "bg_image" => $bg_image,
            "bg_video" => $bg_video,
            "cta_text" => $fields["masthead_has_cta"]
                ? $fields["masthead_cta_text"]
                : null,
            "cta_url" => $fields["masthead_has_cta"]
                ? $fields["masthead_cta_url"]
                : null,
            "label" => $fields["masthead_label"],
        ];
        if (isset($fields["masthead_has_toc"]) && $fields["masthead_has_toc"]) {
            $table_of_contents_links = \Aventyret_headless_wp_blocks::get_h2_blocks(
                $post_content,
                2
            );
            if (!empty($table_of_contents_links)) {
                $masthead["table_of_contents"] = [
                    "links" => $table_of_contents_links,
                ];
            }
        }
        return $masthead;
    }

    public static function get_lead($fields)
    {
        $lead = null;
        if ($fields["lead_has_lead"]) {
            $contact_person_id = $fields["lead_person"];
            $contact_person = get_post($contact_person_id);
            if ($contact_person) {
                $image_src = null;
                $contact_person_thumbnail_id = get_post_thumbnail_id(
                    $contact_person_id
                );
                $contact_person_thumbnail_attributes = $contact_person_thumbnail_id
                    ? \Aventyret_headless_wp_utils::internal_img_attributes(
                        $contact_person_thumbnail_id,
                        [
                            "priority" => true,
                        ]
                    )
                    : null;
                if (!empty($contact_person_thumbnail_attributes)) {
                    $image_src = $contact_person_thumbnail_attributes["src"];
                }
                $contact_person_fields = get_fields($contact_person_id);
                $lead = [
                    "image_src" => $image_src,
                    "name" => $contact_person->post_title,
                    "title" => $fields["lead_title"],
                    "content" => $fields["lead_content"],
                    "email" => $fields["lead_show_email"]
                        ? $contact_person_fields["person_email"]
                        : null,
                    "telephone" => $fields["lead_show_telephone"]
                        ? $contact_person_fields["person_telephone"]
                        : null,
                ];
            }
        }
        return $lead;
    }

    public static function get_related($fields, $post_id)
    {
        $related = null;
        if (
            $fields["related_has_related"] &&
            !empty($fields["related_posts"])
        ) {
            $related_posts = $fields["related_posts"];

            $related_posts = array_filter($fields["related_posts"], function (
                $related_post
            ) use ($post_id) {
                return $related_post->ID != $post_id;
            });
            $related = [
                "title" => $fields["related_title"],
                "posts" => array_map(function ($related_post) use ($fields) {
                    return \Aventyret_headless_wp_content::get_post_in_archive(
                        $related_post
                    );
                }, $related_posts),
            ];
        }
        return $related;
    }

    public static function get_post_footer($post_fields)
    {
        $title =
            isset($post_fields["post_footer_title"]) &&
            $post_fields["post_footer_title"]
                ? $post_fields["post_footer_title"]
                : get_field("global_post_footer_title", "global");
        $post_footer = array_reduce(
            ["image", "image_as_bg", "title", "items", "badges"],
            function ($fields, $field) use ($post_fields) {
                $fields[$field] =
                    isset($post_fields["post_footer_" . $field]) &&
                    $post_fields["post_footer_" . $field]
                        ? $post_fields["post_footer_" . $field]
                        : get_field("global_post_footer_" . $field, "global");
                return $fields;
            },
            [
                "image_src" => null,
                "image_width" => null,
                "image_height" => null,
            ]
        );
        if ($post_footer["image"]) {
            $image_attributes = \Aventyret_headless_wp_utils::internal_img_attributes(
                $post_footer["image"]
            );
            if (!empty($image_attributes)) {
                $post_footer["image_src"] = $image_attributes["src"];
                $post_footer["image_width"] = $image_attributes["width"];
                $post_footer["image_height"] = $image_attributes["height"];
            }
        }
        unset($post_footer["image"]);
        if ($post_footer["items"]) {
            $post_footer["items"] = array_map(function ($item) {
                return [
                    "text" => $item["item_text"],
                    "url" =>
                        $item["item_link_type"] == "external"
                            ? $item["item_url"]
                            : \Aventyret_headless_wp_utils::parseUrl(
                                $item["item_link"]
                            ),
                ];
            }, $post_footer["items"]);
        }
        if (
            !$post_fields["post_footer_hide_badges"] &&
            $post_footer["badges"]
        ) {
            $post_footer["badges"] = array_map(function ($badge) {
                return [
                    "image_src" => !empty($badge["badge_image"])
                        ? $badge["badge_image"]
                        : null,
                    "alt" => $badge["badge_alt"],
                    "url" => $badge["badge_post"]
                        ? \Aventyret_headless_wp_utils::parseUrl(
                            get_permalink($badge["badge_post"])
                        )
                        : null,
                ];
            }, $post_footer["badges"]);
        } else {
            $post_footer["badges"] = null;
        }
        return $post_footer;
    }

    public static function parse_takeover_blocks($content)
    {
        $separator = 'data-component="ExpandTakeover" data-takeover-id="';
        if (strpos($content, $separator) !== false) {
            $parsed_content = "";
            foreach (explode($separator, $content) as $i => $part) {
                $parsed_part = $part;
                if ($i > 0) {
                    $parsed_part = $separator . $parsed_part;
                    $takeover_id = substr($part, 0, strpos($part, '"'));
                    $takeover_thumbnails = get_field(
                        "takeover_thumbnail_images",
                        $takeover_id
                    );
                    $prop_post_id = $takeover_id;
                    $takeover_thumbnails_attributes = array_map(
                        function ($thumnail) {
                            return \Aventyret_headless_wp_utils::internal_img_attributes(
                                $thumnail["image"],
                                [
                                    "size" => "square",
                                ]
                            );
                        },
                        $takeover_thumbnails ? $takeover_thumbnails : []
                    );
                    $props_thumbnails = array_reduce(
                        $takeover_thumbnails_attributes,
                        function ($props, $thumnail_attributes) {
                            return [
                                "src" =>
                                    $props["src"] .
                                    "," .
                                    $thumnail_attributes["src"],
                                "width" =>
                                    $props["width"] .
                                    "," .
                                    $thumnail_attributes["width"],
                                "height" =>
                                    $props["height"] .
                                    "," .
                                    $thumnail_attributes["height"],
                            ];
                        },
                        [
                            "src" => "",
                            "width" => "",
                            "height" => "",
                        ]
                    );
                    $parsed_part = str_replace(
                        'data-takeover-id="' . $takeover_id . '"',
                        'data-takeover-id="' .
                            $takeover_id .
                            '" data-prop-post-id="' .
                            $prop_post_id .
                            '" data-prop-thumbnails-src="' .
                            ltrim($props_thumbnails["src"], ",") .
                            '" data-prop-thumbnails-width="' .
                            ltrim($props_thumbnails["width"], ",") .
                            '" data-prop-thumbnails-height="' .
                            ltrim($props_thumbnails["height"], ",") .
                            '"',
                        $parsed_part
                    );
                }
                $parsed_content .= $parsed_part;
            }
            $content = $parsed_content;
        }
        return $content;
    }

    public static function get_front_page_data($fields)
    {
        $front_page_data = [
            "prioritized_persons" => self::get_prioritized_persons($fields),
        ];
        return $front_page_data;
    }

    public static function get_contact_page_data($fields)
    {
        $contact_page_data = [
            "prioritized_persons" => self::get_prioritized_persons($fields),
        ];

        $contact_page_data["offices"] = get_field("global_offices", "global");
        $contact_page_data["offices_title"] =
            $contact_page_data["offices"] &&
            !empty($contact_page_data["offices"])
                ? $fields["contact_offices_title"]
                : null;

        $contact_page_data["persons"] = array_map(
            function ($person_post) {
                $person_thumbnail_id = get_post_thumbnail_id($person_post->ID);
                $person_thumbnail_attributes = $person_thumbnail_id
                    ? \Aventyret_headless_wp_utils::internal_img_attributes(
                        $person_thumbnail_id,
                        [
                            "size" => "square",
                        ]
                    )
                    : null;
                return [
                    "title" => $person_post->post_title,
                    "description" => null,
                    "telephone" => get_field(
                        "person_telephone",
                        $person_post->ID
                    ),
                    "email" => get_field("person_email", $person_post->ID),
                    "linkedin" => get_field(
                        "person_linkedin",
                        $person_post->ID
                    ),
                    "image_src" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["src"]
                        : null,
                    "image_width" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["width"]
                        : null,
                    "image_height" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["height"]
                        : null,
                ];
            },
            get_posts([
                "posts_per_page" => -1,
                "post_type" => "person",
                "orderby" => "title",
                "order" => "ASC",
            ])
        );
        $contact_page_data["persons_title"] = !empty(
            $contact_page_data["persons"]
        )
            ? $fields["contact_persons_title"]
            : null;

        return $contact_page_data;
    }

    private static function get_prioritized_persons($fields)
    {
        $prioritized_persons = [];
        if (
            isset($fields["contact_prioitized_persons"]) &&
            $fields["contact_prioitized_persons"]
        ) {
            $prioritized_persons["title"] = isset(
                $fields["contact_prioitized_persons_title"]
            )
                ? $fields["contact_prioitized_persons_title"]
                : null;
            $prioritized_persons["text"] = isset(
                $fields["contact_prioitized_persons_text"]
            )
                ? $fields["contact_prioitized_persons_text"]
                : null;
            $prioritized_persons["persons"] = array_map(function (
                $person_fields
            ) {
                $person_thumbnail_id = get_post_thumbnail_id(
                    $person_fields["person"]
                );
                $person_thumbnail_attributes = $person_thumbnail_id
                    ? \Aventyret_headless_wp_utils::internal_img_attributes(
                        $person_thumbnail_id,
                        [
                            "size" => "square",
                        ]
                    )
                    : null;

                $description = $person_fields["description"];
                if (substr_count($description, "</p>") == 1) {
                    if (
                        preg_match("/<p>/", $description) &&
                        preg_match(
                            '/<\/p>$/',
                            str_replace("\n", "", $description)
                        )
                    ) {
                        $description = preg_replace("/^<p>/", "", $description);
                        $description = preg_replace(
                            '/<\/p>$/',
                            "",
                            $description
                        );
                    }
                }

                return [
                    "image_src" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["src"]
                        : null,
                    "image_width" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["width"]
                        : null,
                    "image_height" => !empty($person_thumbnail_attributes)
                        ? $person_thumbnail_attributes["height"]
                        : null,
                    "title" => $person_fields["title"],
                    "description" => $description,
                    "telephone" => get_field(
                        "person_telephone",
                        $person_fields["person"]
                    ),
                    "email" => get_field(
                        "person_email",
                        $person_fields["person"]
                    ),
                ];
            },
            $fields["contact_prioitized_persons"]);
        }
        return $prioritized_persons;
    }

    public static function get_site_meta_header($site_meta)
    {
        $site_meta["header_menu_text"] = get_field(
            "global_header_menu_text",
            "global"
        );
        $header_menu_image_id_large_screens = get_field(
            "global_header_menu_image_large_screens",
            "global"
        );
        $site_meta[
            "header_menu_image_large_screens"
        ] = $header_menu_image_id_large_screens
            ? \Aventyret_headless_wp_utils::internal_img_attributes(
                $header_menu_image_id_large_screens
            )
            : null;
        $header_menu_image_id_small_screens = get_field(
            "global_header_menu_image_small_screens",
            "global"
        );
        $site_meta[
            "header_menu_image_small_screens"
        ] = $header_menu_image_id_small_screens
            ? \Aventyret_headless_wp_utils::internal_img_attributes(
                $header_menu_image_id_small_screens
            )
            : null;

        return $site_meta;
    }

    public static function get_site_meta_cookies($site_meta)
    {
        $site_meta["cookie_consent"] = null;
        if (get_field("global_cookies_show_cookie_consent", "global")) {
            $enabled_cookie_types = get_field(
                "global_cookies_enabled_cookie_types",
                "global"
            );
            $site_meta["cookie_consent"] = [
                "ttl_days" => get_field("global_cookies_ttl_days", "global"),
                "title" => get_field("global_cookies_title", "global"),
                "content" => get_field("global_cookies_content", "global"),
                "button_text_reject" => get_field(
                    "global_cookies_button_text_reject",
                    "global"
                ),
                "button_text_save" => get_field(
                    "global_cookies_button_text_save",
                    "global"
                ),
                "button_text_accept" => get_field(
                    "global_cookies_button_text_accept",
                    "global"
                ),
                "details_link" => \Aventyret_headless_wp_utils::parseUrl(
                    get_field("global_cookies_details_link", "global")
                ),
                "details_text" => get_field(
                    "global_cookies_details_text",
                    "global"
                ),
                "enabled_types" => array_reduce(
                    $enabled_cookie_types ? $enabled_cookie_types : [],
                    function ($enabled_types, $cookie_type) {
                        return array_merge($enabled_types, [
                            [
                                "type" => $cookie_type . "_storage",
                                "name" => get_field(
                                    "global_cookies_" . $cookie_type . "_name",
                                    "global"
                                ),
                                "read_only" => false,
                                "default_enabled" => false,
                            ],
                        ]);
                    },
                    [
                        [
                            "type" => "necessary_storage",
                            "name" => get_field(
                                "global_cookies_necessary_name",
                                "global"
                            ),
                            "read_only" => true,
                            "default_enabled" => true,
                        ],
                    ]
                ),
            ];
        }
        return $site_meta;
    }

    public static function get_site_meta_footer($site_meta)
    {
        $site_meta["footer_copywrite"] = get_field(
            "global_footer_copywrite",
            "global"
        );
        $badges = get_field("global_footer_badges", "global");
        if ($badges) {
            $site_meta["footer_badges"] = array_map(function ($badge) {
                return [
                    "image_src" => !empty($badge["badge_image"])
                        ? $badge["badge_image"]
                        : null,
                    "alt" => $badge["badge_alt"],
                    "url" => $badge["badge_post"]
                        ? \Aventyret_headless_wp_utils::parseUrl(
                            get_permalink($badge["badge_post"])
                        )
                        : null,
                ];
            }, $badges);
        } else {
            $site_meta["footer_badges"] = null;
        }
        return $site_meta;
    }
}
