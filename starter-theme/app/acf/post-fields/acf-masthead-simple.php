<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_63f3a18iwjeh2",
        "title" => __("Masthead", "sage"),
        "fields" => [
            [
                "key" => "field_63f3a82ue625w",
                "label" => __("Title", "sage"),
                "name" => "masthead_title",
                "type" => "text",
                "instructions" => __("The post title is used if empty", "sage"),
            ],
            [
                "key" => "field_63f3a8iu2hwge",
                "label" => __("Text", "sage"),
                "name" => "masthead_text",
                "type" => "textarea",
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
        ],
        "menu_order" => 0,
        "position" => "side",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
