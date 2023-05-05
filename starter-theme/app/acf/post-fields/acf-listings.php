<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_6419c38a2667e",
        "title" => __("Listings", "sage"),
        "fields" => [
            [
                "key" => "field_6419c38ae8a3b",
                "label" => __("SVG", "sage"),
                "name" => "image_icon",
                "type" => "file",
                "instructions" => __(
                    "Typically a white logo. Shown on a black backdrop on top of image in listings.",
                    "sage"
                ),
                "return_format" => "url",
                "library" => "all",
                "mime_types" => "svg",
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
        "menu_order" => 5,
        "position" => "side",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
