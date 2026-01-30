<?php
add_action('rest_api_init', function () {
    register_rest_route(AKKA_API_BASE, '/site_meta', [
        'methods' => 'GET',
        'callback' => 'Akka\SiteMeta::get_site_meta',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/post/(?P<permalink>[a-zA-Z0-9-%+_.]+)', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::permalink_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/post/', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::permalink_request',
        'permission_callback' => 'Akka\Router::can_get_content',
        'args' => [
            'permalink' => [
                'required' => true,
                'type' => 'string',
                'default' => '/',
            ],
        ],
    ]);
    register_rest_route(AKKA_API_BASE, '/posts', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::posts_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/post_by_id/(?P<post_id>[0-9]+)', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::post_by_id_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/attachment_by_id/(?P<attachment_id>[0-9]+)', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::attachment_by_id_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/search/(?P<query>[a-zA-Z0-9-%+_]+)', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::search_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/search', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::search_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/terms', [
        'methods' => 'GET',
        'callback' => 'Akka\Router::terms_request',
        'permission_callback' => 'Akka\Router::can_get_content',
    ]);
    register_rest_route(AKKA_API_BASE, '/editor/block', [
        'methods' => 'POST',
        'callback' => 'Akka\AkkaBlocks::render_editor_block',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});
