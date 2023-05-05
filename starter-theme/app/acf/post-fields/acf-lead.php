<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_640e37876152h",
        "title" => __("Lead", "sage"),
        "fields" => [
            [
                "key" => "field_640e37449e129",
                "label" => __("Show lead", "sage"),
                "name" => "lead_has_lead",
                "type" => "true_false",
            ],
            [
                "key" => "field_640e37779e12a",
                "label" => __("Person", "sage"),
                "name" => "lead_person",
                "type" => "post_object",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_640e37449e129",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
                "required" => "1",
                "post_type" => [
                    0 => "person",
                ],
                "return_format" => "id",
            ],
            [
                "key" => "field_640e37949e12b",
                "label" => __("Title", "sage"),
                "name" => "lead_title",
                "type" => "text",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_640e37449e129",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
                "required" => "1",
            ],
            [
                "key" => "field_640e37bd9e12c",
                "label" => __("Text", "sage"),
                "name" => "lead_content",
                "type" => "wysiwyg",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_640e37449e129",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
                "tabs" => "all",
                "toolbar" => "basic",
                "media_upload" => 0,
                "delay" => 0,
            ],
            [
                "key" => "field_640e37652sedr",
                "label" => __("Show email (if present on person)", "sage"),
                "name" => "lead_show_email",
                "type" => "true_false",
                "default_value" => "1",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_640e37449e129",
                            "operator" => "==",
                            "value" => "1",
                        ],
                    ],
                ],
            ],
            [
                "key" => "field_640e3ikuy6tre",
                "label" => __("Show telephone (if present on person)", "sage"),
                "name" => "lead_show_telephone",
                "type" => "true_false",
                "default_value" => "1",
                "conditional_logic" => [
                    [
                        [
                            "field" => "field_640e37449e129",
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
        ],
        "menu_order" => 1,
        "position" => "acf_after_title",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
