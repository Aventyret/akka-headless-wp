<?php

if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_6411d80be1a4f",
        "title" => __("Related content", "sage"),
        "fields" => [
            [
                "key" => "field_6411d92ec1347",
                "label" => __("Show related content", "sage"),
                "name" => "related_has_related",
                "type" => "true_false",
            ],
            [
                "key" => "field_6411d80c3a8d9",
                "label" => __("Title", "sage"),
                "name" => "related_title",
                "type" => "text",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_6411d92ec1347",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
                "default_value" => "Similar content",
                "maxlength" => "",
                "placeholder" => "",
                "prepend" => "",
                "append" => "",
            ],
            [
                "key" => "field_6411d8423a8da",
                "label" => __("Related posts", "sage"),
                "name" => "related_posts",
                "type" => "post_object",
                "post_type" => [
                    0 => "page",
                    1 => "case",
                    2 => "post",
                ],
                "return_format" => "object",
                "multiple" => "1",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_6411d92ec1347",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "page",
                ],
                [
                    "param" => "page_template",
                    "operator" => "!=",
                    "value" => "template-contact.blade.php",
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
        "menu_order" => 1,
        "position" => "acf_after_title",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
