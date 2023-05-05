<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_63f3a57c6b493",
        "title" => __("Masthead", "sage"),
        "fields" => [
            [
                "key" => "field_63f3a57ce90b4",
                "label" => __("Title", "sage"),
                "name" => "masthead_title",
                "type" => "text",
                "instructions" => __("The post title is used if empty", "sage"),
            ],
            [
                "key" => "field_63f3a64fe90b5",
                "label" => __("Text", "sage"),
                "name" => "masthead_text",
                "type" => "textarea",
            ],
            [
                "key" => "field_63f4b7c1c2ace",
                "label" => __("Background color", "sage"),
                "name" => "masthead_bg_color",
                "type" => "color_picker",
                "enable_opacity" => 0,
                "return_format" => "string",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_63f3a87654ea4",
                            "operator" => "==",
                            "value" => "",
                        ],
                    ],
                ],
            ],
            [
                "key" => "field_63f3a87654ea4",
                "label" => __("Media in masthead", "sage"),
                "name" => "masthead_media",
                "type" => "radio",
                "choices" => [
                    "" => __("No media", "sage"),
                    "featured" => __("Use featured image", "sage"),
                    "image" => __("Use custom image", "sage"),
                    "video" => __("Use video", "sage"),
                ],
                "default_value" => "",
                "return_format" => "value",
                "default_value" => "",
            ],
            [
                "key" => "field_63f4b7efc2acf",
                "label" => __("Background image", "sage"),
                "name" => "masthead_bg_image",
                "type" => "image",
                "return_format" => "id",
                "library" => "all",
                "preview_size" => "medium",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_63f3a87654ea4",
                            "operator" => "==",
                            "value" => "image",
                        ],
                    ],
                ],
            ],
            [
                "key" => "field_642582c4d72fe",
                "label" => __("Background video", "sage"),
                "name" => "masthead_bg_video",
                "type" => "file",
                "return_format" => "id",
                "library" => "all",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_63f3a87654ea4",
                            "operator" => "==",
                            "value" => "video",
                        ],
                    ],
                ],
                "mime_types" => "mp4",
            ],
            [
                "key" => "field_63f3a8iju3yfg",
                "label" => __("Show CTA", "sage"),
                "name" => "masthead_has_cta",
                "type" => "true_false",
            ],
            [
                "key" => "field_63f3a9ieh2736",
                "label" => __("CTA text", "sage"),
                "name" => "masthead_cta_text",
                "type" => "text",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_63f3a8iju3yfg",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
            ],
            [
                "key" => "field_63f3a7uy36524",
                "label" => __("CTA url", "sage"),
                "name" => "masthead_cta_url",
                "type" => "text",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_63f3a8iju3yfg",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
            ],
            [
                "key" => "field_63f3a673e90b6",
                "label" => __("Label", "sage"),
                "name" => "masthead_label",
                "type" => "text",
                "default_value" => __("Like this", "sage"),
            ],
            [
                "key" => "field_63f3a8y654rf2",
                "label" => __("Show table of contents", "sage"),
                "name" => "masthead_has_toc",
                "type" => "true_false",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "page",
                ],
            ],
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "business_area",
                ],
            ],
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "product",
                ],
            ],
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "case",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "side",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
