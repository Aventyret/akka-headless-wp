<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_643e86efc8650",
        "title" => __("Cookie consent", "sage"),
        "fields" => [
            [
                "key" => "field_643e8726EEA62",
                "label" => __("Show cookie consent", "sage"),
                "name" => "global_cookies_show_cookie_consent",
                "type" => "true_false",
                "instructions" => "",
            ],
            [
                "key" => "field_643e8827eac26",
                "label" => __("Cookies TTL in days", "sage"),
                "name" => "global_cookies_ttl_days",
                "type" => "number",
                "instructions" => __(
                    "Number of days to store cookie consent",
                    "sage"
                ),
                "default_value" => 365,
            ],
            [
                "key" => "field_643e86f0fae24",
                "label" => __("Title", "sage"),
                "name" => "global_cookies_title",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("We use cookies", "sage"),
            ],
            [
                "key" => "field_643e87a6fae25",
                "label" => __("Content", "sage"),
                "name" => "global_cookies_content",
                "type" => "wysiwyg",
                "tabs" => "all",
                "toolbar" => "basic",
                "media_upload" => 0,
                "delay" => 0,
            ],
            [
                "key" => "field_643e8aa12ecea",
                "label" => __(
                    __("Button text, reject all (except necessary)", "sage"),
                    "sage"
                ),
                "name" => "global_cookies_button_text_reject",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("Allow necessary", "sage"),
            ],
            [
                "key" => "field_643e86591ecae",
                "label" => __("Button text, save", "sage"),
                "name" => "global_cookies_button_text_save",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("Save selected", "sage"),
            ],
            [
                "key" => "field_643e81515ca76",
                "label" => __("Button text, accept all", "sage"),
                "name" => "global_cookies_button_text_accept",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("Accept all", "sage"),
            ],
            [
                "key" => "field_643e87dbfae26",
                "label" => __("Details link", "sage"),
                "name" => "global_cookies_details_link",
                "type" => "page_link",
                "post_type" => [
                    0 => "page",
                ],
                "allow_archives" => 1,
                "multiple" => 0,
                "allow_null" => 0,
            ],
            [
                "key" => "field_643e86f0e3727",
                "label" => __("Details text", "sage"),
                "name" => "global_cookies_details_text",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("Show details", "sage"),
            ],
            [
                "key" => "field_643e8811fae27",
                "label" => __("Necessary cookies name", "sage"),
                "name" => "global_cookies_necessary_name",
                "type" => "text",
                "instructions" => "",
                "default_value" => __("Necessary", "sage"),
            ],
            [
                "key" => "field_643eb90a5369a",
                "label" => __("Cookie types", "sage"),
                "name" => "global_cookies_enabled_cookie_types",
                "type" => "select",
                "instructions" => __(
                    "Select what cookie types to enable consent for (apart from necessary cookies)",
                    "sage"
                ),
                "choices" => [
                    "functional" => __("Functional", "sage"),
                    "analytical" => __("Analytical", "sage"),
                    "marketing" => __("Marketing", "sage"),
                ],
                "default_value" => ["functional", "analytical", "marketing"],
                "return_format" => "value",
                "multiple" => 1,
            ],
            [
                "key" => "field_643e8882fae29",
                "label" => __("Functional cookies name", "sage"),
                "name" => "global_cookies_functionality_name",
                "type" => "text",
                "instructions" => "",
                "required" => 1,
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_643eb90a5369a",
                            "operator" => "==contains",
                            "value" => "functionality",
                        ],
                    ],
                ],
                "default_value" => __("Functional", "sage"),
            ],
            [
                "key" => "field_643e884ffae28",
                "label" => __("Analytical cookies name", "sage"),
                "name" => "global_cookies_analytics_name",
                "type" => "text",
                "instructions" => "",
                "required" => 1,
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_643eb90a5369a",
                            "operator" => "==contains",
                            "value" => "analytics",
                        ],
                    ],
                ],
                "default_value" => __("Analytical", "sage"),
            ],
            [
                "key" => "field_643e88b3fae2a",
                "label" => __("Marketing cookies name", "sage"),
                "name" => "global_cookies_ad_name",
                "type" => "text",
                "instructions" => "",
                "required" => 1,
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_643eb90a5369a",
                            "operator" => "==contains",
                            "value" => "ad",
                        ],
                    ],
                ],
                "default_value" => __("Marketing", "sage"),
            ],
        ],
        "location" => [
            [
                [
                    "param" => "options_page",
                    "operator" => "==",
                    "value" => "global-cookies",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "normal",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
