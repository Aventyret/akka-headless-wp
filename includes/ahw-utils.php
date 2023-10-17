<?php
class Akka_headless_wp_utils {
  public static function isHeadless() {
    return defined('REST_REQUEST') && !is_user_logged_in();
  }

  public static function getRouteParam($data, $param, $default = NULL) {
    return isset($data[$param]) ? $data[$param] : $default;
  }

  public static function getQueryParam($param, $default = NULL) {
    return isset($_GET[$param]) ? $_GET[$param] : $default;
  }

  public static function stringToRoute($string) {
    return str_replace([' ', 'å', 'ä', 'ö'], ['-', 'a', 'a', 'o'], strtolower($string));
  }

  public static function parseUrl($url) {
    $url = str_replace(WP_HOME . '/', '/', $url);
    $url = str_replace(WP_HOME, '/', $url);
    if ($url !== '/') {
      $url = rtrim($url, '/');
    }
    return $url;
  }

  public static function replaceHrefs($content) {
    if (!self::isHeadless()) {
        return $content;
    }

    $content = str_replace('href="' . WP_HOME . '/', 'data-internal-link="true" href="/', $content);
    $content = str_replace('href="' . WP_HOME, 'data-internal-link="true" href="/', $content);
    $content = str_replace('href="#', 'data-internal-link="true" href="#', $content);
    $content = str_replace('data-internal-link="true" href="/app/', 'href="' . WP_HOME . '/', $content);
    $content = str_replace('href="/app/', 'href="' . WP_HOME . '/app/', $content);

    return $content;
  }

  public static function replaceHtmlCharachters($content) {
    if (!self::isHeadless() || !$content) {
        return $content;
    }
    return str_replace('&amp;shy;', '&shy;', $content);
  }

  public static function replaceSrcs($content) {
    if (!self::isHeadless()) {
        return $content;
    }

    $content = str_replace('src="' . WP_HOME . '/', 'src="/', $content);
    $content = str_replace('src="/', 'data-internal-image="true" src="' . AKKA_CMS_INTERNAL_BASE . '/', $content);
    if (AKKA_CMS_MEDIA_BUCKET_BASE) {
      $content = str_replace('src="' . AKKA_CMS_MEDIA_BUCKET_BASE, 'data-internal-image="true" src="' . AKKA_CMS_MEDIA_BUCKET_BASE, $content);
    }

    return $content;
  }

  public static function parseWysiwyg($html) {
    if (!self::isHeadless() || !$html) {
        return $html;
    }

    return self::replaceHrefs($html);
  }

  public static function internal_img_tag($img_id, $img_attributes = []) {
    $img_attributes = self::internal_img_attributes($img_id, $img_attributes);
    if (empty($img_attributes)) {
      return '';
    }
    return self::isHeadless() ? self::internal_img_tag_in_frontend($img_attributes) : self::internal_img_tag_in_cms($img_attributes);
  }

  public static function internal_img_attributes($img_id, $img_attributes = []) {
    if (empty($img_id)) {
      return [];
    }
    $img_size = isset($img_attributes['size']) ? $img_attributes['size'] : "full";
    $img_src_data = wp_get_attachment_image_src(
      $img_id,
      $img_size,
    );
    if (empty($img_src_data)) {
      return [];
    }
    $img_attributes['id'] = $img_id;
    $img_attributes['src'] = $img_src_data[0];
    if (strpos($img_attributes['src'], '/') === 0) {
      $img_attributes['src'] = AKKA_CMS_INTERNAL_BASE . $img_attributes['src'];
    }
    $img_attributes['width'] = $img_src_data[1];
    $img_attributes['height'] = $img_src_data[2];
    if (!isset($img_attributes['alt'])) {
      $img_attributes['alt'] = get_post_meta($img_id, '_wp_attachment_image_alt', TRUE);
      if (!$img_attributes['alt']) {
        $img_attributes['alt'] = '';
      }
    }
    return $img_attributes;
  }

  private static function internal_img_tag_in_frontend($img_attributes) {
    $img_tag = '<img data-internal-image="true" ';
    foreach($img_attributes as $attr => $value) {
      if (!in_array($attr, ['size'])) {
        $img_tag .= $attr . '="' . $value . '" ';
      }
    }
    $img_tag .= '/>';
    return $img_tag;
  }

  private static function internal_img_tag_in_cms($img_attributes) {
    return '<img src="' . $img_attributes['src'] . '" alt="' . $img_attributes['alt'] . '" />';
  }

  public static function strip_single_wrapping_paragraph($html) {
    if (substr_count($html, "</p>") == 1) {
      if (
        preg_match("/<p>/", $html) &&
        preg_match(
          '/<\/p>$/',
          str_replace("\n", "", $html)
        )
      ) {
        $html = preg_replace("/^<p>/", "", $html);
        $html = preg_replace(
          '/<\/p>$/',
          "",
          $html
        );
      }
    }
    return $html;
  }

  public static function redirect_to_frontend() {
    if (wp_is_json_request()) {
      return;
    }
    if (is_admin()) {
      return;
    }
    if (strpos($_SERVER['REQUEST_URI'], '/wp-') === 0) {
      return;
    }
    if (strpos($_SERVER['REQUEST_URI'], '/sitemap') === 0) {
      return;
    }
    if (strpos($_SERVER['REQUEST_URI'], '/robots.txt') === 0) {
      return;
    }
    if ($_SERVER['REQUEST_URI'] == '/') {
      wp_redirect(WP_SITEURL . '/wp-admin');
    }
    $redirect_uri = $_SERVER['REQUEST_URI'];
    if (is_user_logged_in() && isset($_GET['p']) && $_GET['p'] && !str_starts_with($redirect_uri, '/draft/')) {
      $redirect_uri = '/draft' . $redirect_uri;
      if (!isset($_GET['preview'])) {
        $redirect_uri .= '&preview=true';
      }
    }
    wp_redirect(AKKA_FRONTEND_BASE . $redirect_uri);
  }

  public static function enqueue_frontend_styles() {
    $version = rand(10000, 99999);
    wp_enqueue_style('editor-frontend-styles', AKKA_FRONTEND_BASE . '/cms.css', [], $version);
  }

  public static function check_cms_cookie() {
    if (!AKKA_CMS_COOKIE_PATH) {
      return;
    }
    if (self::isHeadless()) {
      return;
    }
    if (is_user_logged_in() && !isset($_COOKIE[AKKA_CMS_COOKIE_PATH])) {
      self::set_cms_cookie();
    }
  }

  public static function set_cms_cookie() {
    if (!AKKA_CMS_COOKIE_PATH) {
      return;
    }
    $user = wp_get_current_user();
    setcookie(AKKA_CMS_COOKIE_NAME, '1', time() + 86400, '/', AKKA_CMS_COOKIE_PATH); // expire in a day
  }

  public static function remove_cms_cookie() {
    if (!AKKA_CMS_COOKIE_PATH) {
      return;
    }
    setcookie(AKKA_CMS_COOKIE_NAME, '', time() - 3600, '/', AKKA_CMS_COOKIE_PATH);
  }

  public static function flush_frontend_cache() {
    $ok = wp_remote_post(AKKA_FRONTEND_INTERNAL_BASE . AKKA_FRONTEND_FLUSH_CAHCE_ENDPOINT, [
      'headers'     => array(
        'Authorization' => 'Bearer ' . AKKA_FRONTEND_FLUSH_CACHE_KEY,
      )
    ]);
  }

  public static function get_page_template_slug($post) {
    if ($post->post_type === 'page') {
      $page_template = get_page_template_slug($post->ID);
      if ($page_template) {
        return str_replace(['template-', '.blade', '.php'], ['', '', ''], $page_template);
      }
    }
    return NULL;
  }

  public static function wrap_left_and_right_aligned_blocks($html_string, $options = []) {
    if (!$html_string) {
      return $html_string;
    }

    if (strpos($html_string, 'alignleft') === FALSE && strpos($html_string, 'alignright') === FALSE) {
      return $html_string;
    }

    $dom = self::parse_html($html_string);
    if (!$dom) {
      return $html_string;
    }

    $dom_with_wraps = $dom;
    $wrap_node = false;
    $wrapper_html = NULL;
    foreach($dom->nodes as $index => $node) {
      $parent_tag = $node->parent ? $node->parent->tag : NULL;
      if ($parent_tag == 'root') {
        if (!$wrap_node && isset($node->attr['class']) && in_array('alignleft', explode(' ', $node->attr['class']))) {
          $wrap_node = true;
          $wrapper_html = '<div class="align-wrapper align-wrapper--left">';
        }
        if (!$wrap_node && isset($node->attr['class']) && in_array('alignright', explode(' ', $node->attr['class']))) {
          $wrap_node = true;
          $wrapper_html = '<div class="align-wrapper align-wrapper--right">';
        }
        if ($wrap_node && in_array($node->tag, ['text', 'p', 'ul', 'li', 'figure'])) {
          $wrapper_html .= $node->outertext;
          $dom_with_wraps->nodes[$index]->outertext = '';
        } else if($wrap_node) {
          $wrapper_html .= '</div>';
          $dom_with_wraps->nodes[$index]->outertext = $wrapper_html . $node->outertext;
          $wrap_node = false;
          $wrapper = NULL;
        }
      }
    }
    if ($wrap_node) {
      $wrapper_html .= '</div>';
      return $dom_with_wraps->save() . $wrapper_html;
    }
    //$dom->nodes[] = $dom->$nodes[0];
    return $dom_with_wraps->save();
  }

  public static function parse_html($html_string) {
    return str_get_html($html_string);
  }
}
