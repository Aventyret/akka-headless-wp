<?php
if (function_exists("acf_add_local_field_group")):
    acf_add_local_field_group([
        "key" => "group_6418516273ea7",
        "title" => __("Global Post Footer", "sage"),
        "fields" => [
            [
                "key" => "field_64185112376ae",
                "label" => __("Copywrite text", "sage"),
                "name" => "global_footer_copywrite",
                "type" => "text",
            ],
        ],
        "location" => [
            [
                [
                    "param" => "options_page",
                    "operator" => "==",
                    "value" => "global-footer",
                ],
            ],
        ],
        "menu_order" => 0,
        "active" => true,
        "show_in_rest" => 0,
    ]);
endif;
