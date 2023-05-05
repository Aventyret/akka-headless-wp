<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_640eeb5e690b4",
        "title" => __("Contact information", "sage"),
        "fields" => [
            [
                "key" => "field_640eeb5e673ef",
                "label" => __("Email", "sage"),
                "name" => "person_email",
                "type" => "email",
            ],
            [
                "key" => "field_640eeb7c673f0",
                "label" => __("Telephone", "sage"),
                "name" => "person_telephone",
                "type" => "text",
                "maxlength" => "",
            ],
            [
                "key" => "field_640ee27cafe89",
                "label" => __("LinkedIn", "sage"),
                "name" => "person_linkedin",
                "type" => "url",
                "maxlength" => "",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "post_type",
                    "operator" => "==",
                    "value" => "person",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "acf_after_title",
        "active" => true,
    ]);
endif;
