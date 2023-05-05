<?php

if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_featured_people",
        "title" => __("Featured people", "sage"),
        "fields" => [
            [
                "key" => "field_contact_prioitized_persons_title",
                "label" => __("Title", "sage"),
                "name" => "contact_prioitized_persons_title",
                "type" => "text",
            ],
            [
                "key" => "field_contact_prioitized_persons_text",
                "label" => __("Text", "sage"),
                "name" => "contact_prioitized_persons_text",
                "type" => "textarea",
            ],
            [
                "key" => "field_contact_prioitized_persons",
                "label" => __("Featured people", "sage"),
                "name" => "contact_prioitized_persons",
                "type" => "repeater",
                "layout" => "row",
                "button_label" => __("Add person", "sage"),
                "rows_per_page" => 20,
                "sub_fields" => [
                    [
                        "key" =>
                            "field_contact_prioitized_persons_person_title",
                        "label" => __("Title", "sage"),
                        "name" => "title",
                        "type" => "text",
                        "required" => "1",
                        "parent_repeater" => "field_contact_prioitized_persons",
                    ],
                    [
                        "key" => "field_contact_prioitized_persons_person",
                        "label" => __("Person", "sage"),
                        "name" => "person",
                        "type" => "post_object",
                        "required" => "1",
                        "post_type" => [
                            0 => "person",
                        ],
                        "return_format" => "id",
                        "multiple" => 0,
                        "parent_repeater" => "field_contact_prioitized_persons",
                    ],
                    [
                        "key" =>
                            "field_contact_prioitized_persons_person_description",
                        "label" => __("Description", "sage"),
                        "name" => "description",
                        "type" => "wysiwyg",
                        "required" => "1",
                        "tabs" => "all",
                        "toolbar" => "basic",
                        "media_upload" => 0,
                        "parent_repeater" => "field_contact_prioitized_persons",
                    ],
                ],
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
            [
                [
                    "param" => "page_type",
                    "operator" => "==",
                    "value" => "front_page",
                ],
            ],
        ],
        "menu_order" => 0,
        "position" => "acf_after_title",
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
