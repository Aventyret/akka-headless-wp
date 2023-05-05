<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_643945a02b426",
        "title" => "Testimonial",
        "fields" => [
            [
                "key" => "field_643945a0fe365",
                "label" => "Business title",
                "name" => "testimonial_business_title",
                "type" => "text",
            ],
            [
                "key" => "field_643945d8fe366",
                "label" => "Default text",
                "name" => "testimonial_default_text",
                "type" => "textarea",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "testimonial",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "normal",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
