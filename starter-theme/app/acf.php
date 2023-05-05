<?php

add_action("acf/init", function () {
    //    collect(glob(config('theme.dir') . '/app/acf/options/*.php'))->map(function ($file) {
    //        if (function_exists('acf_add_options_page') && function_exists('acf_add_local_field_group')) {
    //            return require_once $file;
    //        }
    //    });
    if (function_exists("acf_add_local_field_group")) {
        require_once get_template_directory() . "/app/acf/options-pages.php";
    }
    collect(glob(get_template_directory() . "/app/acf/post-fields/*.php"))->map(
        function ($file) {
            if (function_exists("acf_add_local_field_group")) {
                return require_once $file;
            }
        }
    );
    collect(
        glob(get_template_directory() . "/app/acf/options-fields/*.php")
    )->map(function ($file) {
        if (function_exists("acf_add_local_field_group")) {
            return require_once $file;
        }
    });
});
