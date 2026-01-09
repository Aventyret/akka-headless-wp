<?php
/*
Plugin Name: Akka Headless WP
Plugin URI: https://github.com/aventyret/akka-wp/blob/main/plugins/akka-headless-wp
Description: Use Wordpress as a headless CMS, with Gutenberg as the content provider
Author: Mediakooperativet, Äventyret
Author URI: https://aventyret.com
Version: 2.0.0
*/

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
    die('Invalid URL');
}

if (defined('AKKA_HEADLESS_WP'))
{
    die('Invalid plugin access');
}

define('AKKA_HEADLESS_WP',  __FILE__ );
define('AKKA_HEADLESS_WP_DIR', plugin_dir_path( __FILE__ ));
define('AKKA_HEADLESS_WP_URL', plugin_dir_url( __FILE__ ));
define('AKKA_HEADLESS_WP_VER', "2.0.0");
define('AKKA_API_BASE', "akka/v2");
define('AKKA_LANG', getenv('AKKA_LANG') ? getenv('AKKA_LANG') : "en");
define('AKKA_CMS_COOKIE_PATH', getenv('AKKA_CMS_COOKIE_PATH') ? getenv('AKKA_CMS_COOKIE_PATH') : NULL);
define('AKKA_CMS_COOKIE_NAME', getenv('AKKA_CMS_COOKIE_NAME') ? getenv('AKKA_CMS_COOKIE_NAME') : "cms_signed_in");
define('AKKA_FRONTEND_BASE', getenv('AKKA_FRONTEND_URL') ? getenv('AKKA_FRONTEND_URL') : 'https://example.com');
define('AKKA_FRONTEND_INTERNAL_BASE', getenv('AKKA_FRONTEND_URL_INTERNAL') ? getenv('AKKA_FRONTEND_URL_INTERNAL') : AKKA_FRONTEND_BASE);
define('AKKA_CMS_INTERNAL_BASE', getenv('AKKA_CMS_URL_INTERNAL') ? getenv('AKKA_CMS_URL_INTERNAL') : WP_HOME);
define('AKKA_CMS_MEDIA_BUCKET_BASE', getenv('AKKA_CMS_MEDIA_BUCKET_HOSTNAME') ? getenv('AKKA_CMS_MEDIA_BUCKET_PROTOCOL') . '://' . getenv('AKKA_CMS_MEDIA_BUCKET_HOSTNAME') . getenv('AKKA_CMS_MEDIA_BUCKET_PORT') : NULL);
define('AKKA_FRONTEND_FLUSH_CACHE_ENDPOINT', getenv('AKKA_FRONTEND_FLUSH_CACHE_ENDPOINT') ? getenv('AKKA_FRONTEND_FLUSH_CACHE_ENDPOINT') :  '/api/cache');
define('AKKA_FRONTEND_FLUSH_CACHE_KEY', getenv('AKKA_FRONTEND_FLUSH_CACHE_KEY') ? getenv('AKKA_FRONTEND_FLUSH_CACHE_KEY') : "");

if (!function_exists('str_get_html')) {
    require_once(AKKA_HEADLESS_WP_DIR . 'vendor/simplehtmldom/simple_html_dom.php');
}
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-utils.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-router.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-site-meta.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-post.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-term.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-archive.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-search.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-blocks.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-resolvers.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-akka-blocks.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-meta-fields.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-acf.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-post-types.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-taxonomies.php');
require_once(AKKA_HEADLESS_WP_DIR . 'includes/akka-wpcli-with-lock.php');
require_once(AKKA_HEADLESS_WP_DIR . 'public/akka-hooks.php');
require_once(AKKA_HEADLESS_WP_DIR . 'public/akka-api-endpoints.php');
require_once(AKKA_HEADLESS_WP_DIR . 'public/akka-healthz.php');
