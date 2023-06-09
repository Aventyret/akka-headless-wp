<?php
add_action( 'rest_api_init', function () {
  register_rest_route( AKKA_API_BASE, '/site_meta', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_site_meta',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/post/(?P<permalink>[a-zA-Z0-9-%+]+)', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_post',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/posts', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_posts',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/post_by_id/(?P<post_id>[0-9]+)', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_post_by_id',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/term/(?P<taxonomy_slug>[a-zA-Z0-9-%+]+)/(?P<term_slug>[a-zA-Z0-9-%+]+)', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_term',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/author/(?P<author_slug>[a-zA-Z0-9-%+]+)', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::get_author',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
  register_rest_route( AKKA_API_BASE, '/search/(?P<query>[a-zA-Z0-9-%+]+)', array(
    'methods' => 'GET',
    'callback' => 'Akka_headless_wp_content::search',
    'permission_callback' => 'Akka_headless_wp_content::can_get_content',
  ) );
} );
