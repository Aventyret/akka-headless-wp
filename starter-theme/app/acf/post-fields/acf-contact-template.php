<?php

if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_contact_page",
        "title" => __("Contact page", "sage"),
        "fields" => [
            [
                "key" => "field_contact_offices_title",
                "label" => __("Offices, title", "sage"),
                "name" => "contact_offices_title",
                "type" => "text",
            ],
            [
                "key" => "field_contact_persons_title",
                "label" => __("Employees, title", "sage"),
                "name" => "contact_persons_title",
                "type" => "text",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "page_template",
                    "operator" => "==",
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
