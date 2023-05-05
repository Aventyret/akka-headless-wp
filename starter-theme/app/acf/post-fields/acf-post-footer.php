<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_64187c6ba6e0d",
        "title" => __("Post Footer Overrides", "sage"),
        "fields" => [
            [
                "key" => "field_64187c6bb93e1",
                "label" => __("Image", "sage"),
                "name" => "post_footer_image",
                "type" => "image",
                "instructions" => __(
                    "Here you can override the image defined in Global Content.",
                    "sage"
                ),
                "return_format" => "id",
                "preview_size" => "medium",
            ],
            [
                "key" => "field_63f3a63eaca24",
                "label" => __("Show image as background", "sage"),
                "name" => "post_footer_image_as_bg",
                "type" => "true_false",
            ],
            [
                "key" => "field_64187c6bb947d",
                "label" => __("Title", "sage"),
                "name" => "post_footer_title",
                "type" => "text",
                "instructions" => __(
                    "Here you can override the title defined in Global Content.",
                    "sage"
                ),
            ],
            [
                "key" => "field_64187adae7288",
                "label" => __("Links", "sage"),
                "name" => "post_footer_items",
                "type" => "repeater",
                "instructions" => __(
                    "Here you can set unique links that overrides the ones defined in Global Content.",
                    "sage"
                ),
                "layout" => "row",
                "button_label" => __("Add item"),
                "rows_per_page" => 20,
                "sub_fields" => [
                    [
                        "key" => "field_64187c6c91150",
                        "label" => __("Text", "sage"),
                        "name" => "item_text",
                        "aria-label" => "",
                        "type" => "text",
                        "required" => 1,
                        "parent_repeater" => "field_64187adae7288",
                    ],
                    [
                        "key" => "field_63f3a8236eaaa",
                        "label" => __("Link type", "sage"),
                        "name" => "item_link_type",
                        "type" => "radio",
                        "choices" => [
                            "internal" => __("Internal", "sage"),
                            "external" => __("External", "sage"),
                        ],
                        "default_value" => "internal",
                        "return_format" => "value",
                        "parent_repeater" => "field_64187adae7288",
                    ],
                    [
                        "key" => "field_64187c6c91209",
                        "label" => __("Link", "sage"),
                        "name" => "item_link",
                        "type" => "page_link",
                        "post_type" => [
                            0 => "post",
                            1 => "page",
                            2 => "case",
                        ],
                        "allow_archives" => 1,
                        "multiple" => 0,
                        "allow_null" => 0,
                        "conditional_logic" => [
                            [
                                [
                                    "field" => "field_63f3a8236eaaa",
                                    "operator" => "==",
                                    "value" => "internal",
                                ],
                            ],
                        ],
                        "required" => 1,
                        "parent_repeater" => "field_64187adae7288",
                    ],
                    [
                        "key" => "field_64185caca3728",
                        "label" => __("Url", "sage"),
                        "name" => "item_url",
                        "type" => "url",
                        "conditional_logic" => [
                            [
                                [
                                    "field" => "field_63f3a8236eaaa",
                                    "operator" => "==",
                                    "value" => "external",
                                ],
                            ],
                        ],
                        "required" => "1",
                        "parent_repeater" => "field_64187adae7288",
                    ],
                ],
            ],
            [
                "key" => "field_63f3a725eac87",
                "label" => __("Hide badges", "sage"),
                "name" => "post_footer_hide_badges",
                "type" => "true_false",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "post",
                ],
            ],
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
        "menu_order" => 3,
        "position" => "acf_after_title",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
