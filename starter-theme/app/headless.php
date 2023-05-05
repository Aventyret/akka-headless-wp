<?php

namespace App;

class Akka_content
{
    public static function get_site_meta_header($site_meta)
    {
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
                "details_link" => \Akka_headless_wp_utils::parseUrl(
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
        return $site_meta;
    }
}
