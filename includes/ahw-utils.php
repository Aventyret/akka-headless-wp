<?php
class Akka_headless_wp_utils
{
    public static function isHeadless()
    {
        return defined('REST_REQUEST') && !is_user_logged_in();
    }

    public static function getRouteParam($data, $param, $default = null)
    {
        return isset($data[$param]) ? $data[$param] : $default;
    }

    public static function getQueryParam($param, $default = null)
    {
        return isset($_GET[$param]) ? $_GET[$param] : $default;
    }

    public static function stringToRoute($string)
    {
        return str_replace([' ', 'å', 'ä', 'ö'], ['-', 'a', 'a', 'o'], strtolower($string));
    }

    public static function parseUrl($url)
    {
        $url = str_replace(WP_HOME . '/', '/', $url);
        $url = str_replace(WP_HOME, '/', $url);
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }
        return apply_filters('ahw_post_parse_url', $url);
    }

    public static function enqueue_editor_assets()
    {
        wp_enqueue_script(
            'akka',
            AKKA_HEADLESS_WP_URL . 'dist/editor.js',
            ['editor', 'wp-data', 'wp-element', 'wp-components'],
            filemtime(AKKA_HEADLESS_WP_DIR . '/dist/editor.js')
        );

        wp_enqueue_style(
            'akka',
            AKKA_HEADLESS_WP_URL . 'dist/editor.css',
            [],
            filemtime(AKKA_HEADLESS_WP_DIR . '/dist/editor.css')
        );
    }

    public static function replaceHrefs($content)
    {
        if (!self::isHeadless()) {
            return $content;
        }

        $content = str_replace(
            'href="' . AKKA_FRONTEND_BASE . '/wp-content/uploads/',
            'href="' . WP_HOME . '/app/uploads/',
            $content
        );
        $content = str_replace(
            'href="' . WP_HOME . '/wp-content/uploads/',
            'href="' . WP_HOME . '/app/uploads/',
            $content
        );
        $content = str_replace('href="' . WP_HOME . '/', 'data-internal-link="true" href="/', $content);
        $content = str_replace('href="' . WP_HOME, 'data-internal-link="true" href="/', $content);
        $content = str_replace('href="#', 'data-internal-link="true" href="#', $content);
        $content = str_replace('data-internal-link="true" href="/app/', 'href="' . WP_HOME . '/app/', $content);
        $content = str_replace('href="/app/', 'href="' . WP_HOME . '/app/', $content);

        return apply_filters('ahw_post_replace_hrefs', $content);
    }

    public static function replaceHtmlCharachters($content)
    {
        if (!self::isHeadless() || !$content) {
            return $content;
        }
        return str_replace('&amp;shy;', '&shy;', $content);
    }

    public static function replaceSrcs($content)
    {
        if (!self::isHeadless()) {
            return $content;
        }

        $content = str_replace(' src="' . WP_HOME . '/', ' src="/', $content);
        $content = str_replace(' src="/', ' data-internal-image="true" src="' . AKKA_CMS_INTERNAL_BASE . '/', $content);
        $content = str_replace('data-internal-image="true" src="' . AKKA_CMS_INTERNAL_BASE . '//', 'src="//', $content);
        if (AKKA_CMS_MEDIA_BUCKET_BASE) {
            $content = str_replace(
                ' src="' . AKKA_CMS_MEDIA_BUCKET_BASE,
                ' data-internal-image="true" src="' . AKKA_CMS_MEDIA_BUCKET_BASE,
                $content
            );
            $content = str_replace(' src="/', ' src="' . AKKA_CMS_MEDIA_BUCKET_BASE . '/', $content);
        }

        return $content;
    }

    public static function parseWysiwyg($html)
    {
        if (!self::isHeadless() || !$html) {
            return $html;
        }

        return self::replaceHrefs($html);
    }

    public static function internal_img_tag($img_id, $img_attributes = [])
    {
        $img_attributes = self::internal_img_attributes($img_id, $img_attributes);
        if (empty($img_attributes)) {
            return '';
        }
        return self::isHeadless()
            ? self::internal_img_tag_in_frontend($img_attributes)
            : self::internal_img_tag_in_cms($img_attributes);
    }

    public static function adjust_media_path($src)
    {
        if (strpos($src, '/') === 0) {
            $src = AKKA_CMS_INTERNAL_BASE . $src;
        }
        if (AKKA_CMS_MEDIA_BUCKET_BASE) {
            $src = str_replace(AKKA_CMS_INTERNAL_BASE, AKKA_CMS_MEDIA_BUCKET_BASE, $src);
            $src = str_replace(WP_HOME, AKKA_CMS_MEDIA_BUCKET_BASE, $src);
        }
        return $src;
    }

    public static function internal_audio_and_video_attributes($media_id, $media_attributes = [])
    {
        if (empty($media_id)) {
            return [];
        }

        $media_src = wp_get_attachment_url($media_id);
        if (empty($media_src)) {
            return [];
        }

        $media_attributes['id'] = $media_id;
        $media_attributes['mime_type'] = get_post_mime_type($media_id);
        $media_attributes['src'] = self::adjust_media_path($media_src);

        if (!isset($media_attributes['title'])) {
            $media_attributes['title'] = get_the_title($media_id);
        }

        $metadata = wp_get_attachment_metadata($media_id);

        if (!empty($metadata)) {
            if (!isset($media_attributes['duration']) && isset($metadata['length'])) {
                $media_attributes['duration'] = $metadata['length'];
            }

            if (!isset($media_attributes['filesize']) && isset($metadata['filesize'])) {
                $media_attributes['filesize'] = $metadata['filesize'];
            }

            if (!isset($media_attributes['bitrate']) && isset($metadata['bitrate'])) {
                $media_attributes['bitrate'] = $metadata['bitrate'];
            }

            if (!isset($media_attributes['codec']) && isset($metadata['codec'])) {
                $media_attributes['codec'] = $metadata['codec'];
            }

            if (!isset($media_attributes['format']) && isset($metadata['format'])) {
                $media_attributes['format'] = $metadata['format'];
            }

            if (!isset($media_attributes['width']) && isset($metadata['width'])) {
                $media_attributes['width'] = $metadata['width'];
            }

            if (!isset($media_attributes['height']) && isset($metadata['height'])) {
                $media_attributes['height'] = $metadata['height'];
            }

            if (!isset($media_attributes['thumbnail'])) {
                if (isset($metadata['image']['url'])) {
                    $media_attributes['thumbnail'] = self::adjust_media_path($metadata['image']['url']);
                } elseif (isset($metadata['thumb'])) {
                    $media_attributes['thumbnail'] = self::adjust_media_path($metadata['thumb']);
                }
            }
        }

        return $media_attributes;
    }

    public static function internal_img_attributes($img_id, $img_attributes = [], $include_caption = false)
    {
        if (empty($img_id)) {
            return [];
        }
        $img_size = isset($img_attributes['size']) ? $img_attributes['size'] : 'full';
        $img_src_data = wp_get_attachment_image_src($img_id, $img_size);
        if (empty($img_src_data)) {
            return [];
        }
        $img_attributes['id'] = $img_id;
        $img_attributes['src'] = self::adjust_media_path($img_src_data[0]);

        if (!isset($img_attributes['width'])) {
            $img_attributes['width'] = $img_src_data[1];
        }
        if (!isset($img_attributes['height'])) {
            $img_attributes['height'] = $img_src_data[2];
        }
        if (!isset($img_attributes['alt'])) {
            $img_attributes['alt'] = get_post_meta($img_id, '_wp_attachment_image_alt', true);
            if (!$img_attributes['alt']) {
                $img_attributes['alt'] = '';
            }
        }
        if ($include_caption) {
            $img_attributes['caption'] = wp_get_attachment_caption($img_id);
        }
        return apply_filters('ahw_img_attributes', $img_attributes);
    }

    public static function internal_img_with_caption_attributes($img_id, $img_attributes = [])
    {
        return self::internal_img_with_caption_attributes($img_id, $img_attributes, true);
    }

    private static function internal_img_tag_in_frontend($img_attributes)
    {
        $img_tag = '<img data-internal-image="true" ';
        foreach ($img_attributes as $attr => $value) {
            if (!in_array($attr, ['size'])) {
                $img_tag .= $attr . '="' . $value . '" ';
            }
        }
        $img_tag .= '/>';
        return $img_tag;
    }

    private static function internal_img_tag_in_cms($img_attributes)
    {
        return '<img src="' .
            $img_attributes['src'] .
            '" alt="' .
            $img_attributes['alt'] .
            '"' .
            (isset($img_attributes['class']) ? ' class="' . $img_attributes['class'] . '"' : '') .
            ' />';
    }

    public static function external_post_img_src($post_id)
    {
        if (!$post_id) {
            return null;
        }
        $image_id = get_post_thumbnail_id($post_id);
        return self::external_img_src($image_id);
    }

    public static function external_img_src($image_id)
    {
        if (!$image_id) {
            return null;
        }
        $src = wp_get_attachment_url($image_id);
        if (str_starts_with($src, '/')) {
            $src = WP_HOME . $src;
        }
        return $src;
    }

    public static function external_post_img_attributes($post_id, $size = 'full')
    {
        if (!$post_id) {
            return null;
        }
        $image_id = get_post_thumbnail_id($post_id);
        return self::external_img_attributes($image_id);
    }

    public static function external_img_attributes($image_id, $size = 'full')
    {
        if (!$image_id) {
            return null;
        }
        $src = wp_get_attachment_image_src($image_id, $size);
        if (!$src) {
            return null;
        }
        if (str_starts_with($src, '/')) {
            $src = WP_HOME . $src;
        }
        return [
            'src' => $src[0],
            'width' => $src[1],
            'height' => $src[2],
        ];
    }

    public static function strip_single_wrapping_paragraph($html)
    {
        if (substr_count($html, '</p>') == 1) {
            if (preg_match('/<p>/', $html) && preg_match('/<\/p>$/', str_replace("\n", '', $html))) {
                $html = preg_replace('/^<p>/', '', $html);
                $html = preg_replace('/<\/p>$/', '', $html);
            }
        }
        return $html;
    }

    public static function redirect_to_frontend()
    {
        if (wp_is_json_request()) {
            return;
        }
        if (is_admin()) {
            return;
        }
        if (is_feed()) {
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
        if (is_user_logged_in()) {
            // Drafts
            $post_id = null;
            if (isset($_GET['p']) && $_GET['p']) {
                $post_id = $_GET['p'];
            }
            if (isset($_GET['page_id']) && $_GET['page_id']) {
                $post_id = $_GET['page_id'];
                $redirect_uri .= '&p=' . $post_id;
            }
            if ($post_id && !str_starts_with($redirect_uri, '/draft/')) {
                $redirect_uri = '/draft' . $redirect_uri;
                if (!isset($_GET['preview'])) {
                    $redirect_uri .= '&preview=true';
                }
            }
            // Private
            if (get_post_status() == 'private' && !isset($_GET['p'])) {
                $redirect_uri = '/draft?p=' . get_the_id();
            }
        }
        wp_redirect(AKKA_FRONTEND_BASE . $redirect_uri);
    }

    public static function enqueue_frontend_styles()
    {
        $version = rand(10000, 99999);
        wp_enqueue_style('editor-frontend-styles', AKKA_FRONTEND_BASE . '/cms.css', [], $version);
    }

    public static function check_cms_cookie()
    {
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

    public static function set_cms_cookie()
    {
        if (!AKKA_CMS_COOKIE_PATH) {
            return;
        }
        $user = wp_get_current_user();
        setcookie(AKKA_CMS_COOKIE_NAME, '1', time() + 86400, '/', AKKA_CMS_COOKIE_PATH); // expire in a day
    }

    public static function remove_cms_cookie()
    {
        if (!AKKA_CMS_COOKIE_PATH) {
            return;
        }
        setcookie(AKKA_CMS_COOKIE_NAME, '', time() - 3600, '/', AKKA_CMS_COOKIE_PATH);
    }

    public static function flush_frontend_cache()
    {
        // Do not trigger flush cache when editing drafts
        if (get_post_type() && get_post_status() != 'publish') {
            return;
        }
        $ok = wp_remote_post(AKKA_FRONTEND_INTERNAL_BASE . AKKA_FRONTEND_FLUSH_CAHCE_ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . AKKA_FRONTEND_FLUSH_CACHE_KEY,
            ],
        ]);
        do_action('ahw_cache_flushed');
    }

    public static function get_page_template_slug($post)
    {
        if ($post->post_type === 'page') {
            $page_template = get_page_template_slug($post->ID);
            if ($page_template) {
                return str_replace(['template-', '.blade', '.php'], ['', '', ''], $page_template);
            }
        }
        return null;
    }

    public static function wrap_left_and_right_aligned_blocks($html_string, $options = [])
    {
        if (!$html_string) {
            return $html_string;
        }

        if (strpos($html_string, 'alignleft') === false && strpos($html_string, 'alignright') === false) {
            return $html_string;
        }

        $dom = self::parse_html($html_string);
        if (!$dom) {
            return $html_string;
        }

        $dom_with_wraps = $dom;
        $wrap_node = false;
        $wrapper_html = null;
        foreach ($dom->nodes as $index => $node) {
            $parent_tag = $node->parent ? $node->parent->tag : null;
            if ($parent_tag == 'root') {
                if (
                    !$wrap_node &&
                    isset($node->attr['class']) &&
                    in_array('alignleft', explode(' ', $node->attr['class']))
                ) {
                    $wrap_node = true;
                    $wrapper_html = '<div class="align-wrapper align-wrapper--left">';
                }
                if (
                    !$wrap_node &&
                    isset($node->attr['class']) &&
                    in_array('alignright', explode(' ', $node->attr['class']))
                ) {
                    $wrap_node = true;
                    $wrapper_html = '<div class="align-wrapper align-wrapper--right">';
                }
                if ($wrap_node && in_array($node->tag, ['text', 'p', 'ul', 'li', 'figure'])) {
                    $wrapper_html .= $node->outertext;
                    $dom_with_wraps->nodes[$index]->outertext = '';
                } elseif ($wrap_node) {
                    $wrapper_html .= '</div>';
                    $dom_with_wraps->nodes[$index]->outertext = $wrapper_html . $node->outertext;
                    $wrap_node = false;
                    $wrapper = null;
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

    public static function parse_html($html_string)
    {
        return str_get_html($html_string);
    }
}
