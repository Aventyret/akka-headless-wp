<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_641ace5e60c01",
        "title" => __("Takeover", "sage"),
        "fields" => [
            [
                "key" => "field_641ace5e2902d",
                "label" => __("Thumbnail images", "sage"),
                "name" => "takeover_thumbnail_images",
                "type" => "repeater",
                "layout" => "row",
                "button_label" => __("Add thumbnail", "sage"),
                "rows_per_page" => 20,
                "sub_fields" => [
                    [
                        "key" => "field_641acf0b2902e",
                        "label" => __("Image", "sage"),
                        "name" => "image",
                        "type" => "image",
                        "return_format" => "id",
                        "library" => "all",
                        "preview_size" => "square",
                        "parent_repeater" => "field_641ace5e2902d",
                    ],
                ],
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "takeover",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "side",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
