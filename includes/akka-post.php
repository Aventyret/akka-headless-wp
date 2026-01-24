<?php
namespace Akka;

class Post
{
    private static $_single_memory = [];
    public static function get_single($post_id_or_post = null, $post_status = ['publish'], $get_autosaved = false)
    {
        global $post;
        if (is_a($post_id_or_post, 'WP_Post')) {
            $post = $post_id_or_post;
        } else if($post_id_or_post) {
            $posts = get_posts([
                'post__in' => [$post_id_or_post],
                'post_type' => 'any',
                'post_status' => $post_status,
            ]);
            if (empty($posts)) {
                return null;
            }
            $post = $posts[0];
        }
        if (!$post) {
            return null;
        }
        if ($get_autosaved) {
            $autosaved_post = wp_get_post_autosave($post->ID);
            if ($autosaved_post) {
                $post->post_content = $autosaved_post->post_content;
            }
        }

        // TODO: Check this
        if ($post->post_password && !in_array('private', $post_status)) {
            return [
                'post_id' => $post->ID,
                'post_type' => $post->post_type,
                'redirect' => '/protected?p=' . $post->ID,
            ];
        }

        if (isset(self::$_single_memory[$post->ID])) {
            return self::$_single_memory[$post->ID];
        }

        $post_thumbnail_id = get_post_thumbnail_id($post->ID);
        $featured_image_attributes = $post_thumbnail_id
            ? Utils::internal_img_attributes($post_thumbnail_id, [
                'priority' => true,
            ])
            : null;

        $permalink = Utils::parseUrl(str_replace(WP_HOME, '', get_permalink($post->ID)));

        $akka_post = [
            'post_id' => $post->ID,
            'post_date' => get_the_date(get_option('date_format'), $post->ID),
            'post_date_iso' => get_the_date('c', $post->ID),
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'post_password' => $post->post_password,
            'post_parent_id' => $post->post_parent,
            'post_status' => $post->post_status,
            'author' => [
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'url' => AKKA_FRONTEND_BASE . Utils::parseUrl(get_author_posts_url($post->post_author)),
            ],
            'slug' => $post->post_name,
            'excerpt' => post_type_supports($post->post_type, 'excerpt') ? $post->post_excerpt : null,
            'page_template' => Utils::get_page_template_slug($p),
            'featured_image' => $featured_image_attributes,
            'thumbnail_caption' => apply_filters(
                'akka_image_caption',
                get_the_post_thumbnail_caption($post->ID),
                $post_thumbnail_id
            ),
            'permalink' => $permalink,
            'url' => $permalink,
            'taxonomy_terms' => Term::get_single_terms($p),
            'fields' => get_fields($post->ID),
        ];
        foreach (['category', 'post_tag'] as $taxonomy_slug) {
            if (isset($data['taxonomy_terms'][$taxonomy_slug])) {
                $akka_post['primary_' . str_replace('post_tag', 'tag', $taxonomy_slug)] =
                    $akka_post['taxonomy_terms'][$taxonomy_slug]['primary_term'];
            }
        }
        if (
            $akka_post['post_type'] == 'page' &&
            Archive::get_post_type_archive_permalink('post') == $akka_post['slug']
        ) {
            $page = Utils::getQueryParam('page', 1);
            $archive_query = Archive::archive_query('post', $page);
            $akka_post['archive'] = [
                'count' => $archive_query->found_posts,
                'pages' => $archive_query->max_num_pages - $page + 1, // NOTE: Max num pages adjusts to starting page
                'posts' => self::posts_to_blurbs($archive_query->posts),
                'next_page' =>
                    $archive_query->max_num_pages > $page + 1
                        ? '/' . Archive::get_post_type_archive_permalink('post') . '?page=' . ($page + 1)
                        : null,
            ];
        }
        do_action('akka_pre_post_content', $akka_post);
        $akka_post['post_content'] = apply_filters('the_content', $post->post_content);
        $akka_post['seo_meta'] = self::get_seo_meta(
            $post,
            Resolvers::resolve_field($akka_post['featured_image'], 'id')
        );

        $akka_post = apply_filters('akka_post_' . $akka_post['post_type'] . '_single', $akka_post, $post);
        if ($akka_post['page_template']) {
            $akka_post = apply_filters('akka_post_template_' . $akka_post['page_template'] . '_single', $akka_post, $post);
        }
        $akka_post = apply_filters('akka_post_single', $akka_post, $post);
        $akka_post['seo_meta']['schema'] = apply_filters(
            'akka_post_schema',
            Resolvers::resolve_array_field($akka_post['seo_meta'], 'schema'),
            $akka_post
        );

        unset($akka_post['fields']);

        self::$_single_memory[$post->ID] = $akka_post;

        return $akka_post;
    }

    public static function get_blurbs($query_args)
    {
        return self::posts_to_blurbs(get_posts($query_args));
    }

    public static function get_blurb($post_id)
    {
        $posts = get_posts([
            'post__in' => [$post_id],
            'post_type' => 'any',
            'post_status' => 'publish',
        ]);
        if (empty($posts)) {
            return null;
        }
        return self::post_to_blurb($posts[0]);
    }

    public static function posts_to_blurbs($posts)
    {
        $post_datas = array_map(function ($post) {
            if (is_array($post)) {
                return $post;
            }
            return self::post_to_blurb($post);
        }, $posts);
        return $post_datas;
    }

    public static function post_to_blurb($post)
    {
        if (is_array($post)) {
            return $post;
        }
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_attributes = $thumbnail_id
            ? Utils::internal_img_attributes($thumbnail_id, [
                'size' => apply_filters('akka_post_in_archive_image_size', 'full'),
            ])
            : null;

        $post_blurb = [
            'post_id' => $post->ID,
            'post_guid' => $post->guid,
            'post_date' => get_the_date(get_option('date_format'), $post->ID),
            'post_date_iso' => get_the_date('c', $post->ID),
            'url' => Utils::parseUrl(get_permalink($post->ID)),
            'featured_image' => !empty($thumbnail_attributes) ? $thumbnail_attributes : null,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'slug' => $post->post_name,
            'description' => get_the_excerpt($post->ID),
            'taxonomy_terms' => Term::get_blurb_terms($post),
        ];
        $post_blurb = apply_filters('akka_post_' . $post_blurb['post_type'] . '_blurb', $post_blurb, $post);
        return apply_filters('akka_post_blurb', $post_blurb, $post);
    }

    private static function get_seo_meta($post, $post_thumbnail_id = null)
    {
        $seo_meta = [];
        $specific_seo_image_is_defined = false;
        if (function_exists('the_seo_framework')) {
            $seo_fields = [
                'seo_title' => '_genesis_title',
                'seo_description' => '_genesis_description',
                'seo_image_id' => '_social_image_id',
                'og_title' => '_open_graph_title',
                'og_description' => '_open_graph_description',
                'twitter_title' => '_twitter_title',
                'twitter_description' => '_twitter_description',
            ];
            foreach ($seo_fields as $seo_attr => $meta_key) {
                $meta_value = get_post_meta($post->ID, $meta_key, true);
                if ($meta_value) {
                    $seo_meta[$seo_attr] = $meta_value;
                }
            }
        }
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $yoast_class = YoastSEO()->classes->get(\Yoast\WP\SEO\Surfaces\Meta_Surface::class);
            $yoast_meta = $yoast_class->for_post($post->ID);
            $yoast_data = $yoast_meta->get_head()->json;
            $seo_meta = [
                'seo_title' => $yoast_data['title'],
                'og_locale' => $yoast_data['og_locale'],
                'og_type' => $yoast_data['og_type'],
                'canonical_url' => $yoast_data['og_url'],
                'og_site_name' => $yoast_data['og_site_name'],
            ];
            if (isset($yoast_data['description'])) {
                $seo_meta['seo_description'] = $yoast_data['description'];
            }
            if (isset($yoast_data['og_description'])) {
                $seo_meta['og_description'] = $yoast_data['og_description'];
            }
            if (isset($yoast_data['og_image']) && !empty($yoast_data['og_image'])) {
                $seo_meta['seo_image_url'] = $yoast_data['og_image'][0]['url'];
                $seo_meta['seo_image_width'] = $yoast_data['og_image'][0]['width'];
                $seo_meta['seo_image_height'] = $yoast_data['og_image'][0]['height'];
            }
            if (isset($yoast_data['robots'])) {
                $seo_meta['robots'] = [
                    'index' =>
                        isset($yoast_data['robots']['index']) && $yoast_data['robots']['index'] == 'noindex'
                            ? false
                            : true,
                    'follow' =>
                        isset($yoast_data['robots']['follow']) && $yoast_data['robots']['follow'] == 'nofollow'
                            ? false
                            : true,
                ];
            }
            if (Resolvers::resolve_field($yoast_data, 'schema')) {
                $seo_meta['schema'] = [];
                foreach (Resolvers::resolve_array_field($yoast_data['schema'], '@graph') as $graph_data) {
                    if (in_array($graph_data['@type'], ['WebSite', 'Organization'])) {
                        $schema_item = [
                            '@context' => $yoast_data['schema']['@context'],
                            '@type' => $graph_data['@type'],
                            '@id' => AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['@id']),
                            'name' => $graph_data['name'],
                            'url' => AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['url']),
                        ];
                        if ($graph_data['@type'] == 'WebSite') {
                            $schema_item['description'] = Resolvers::resolve_field($graph_data, 'description');
                            $schema_item['inLanguage'] = Resolvers::resolve_field($graph_data, 'inLanguage');
                            if ($search_page_url = apply_filters('akka_schema_search_page_url', null)) {
                                $schema_item['potentialAction'] = [
                                    '@type' => 'SearchAction',
                                    'target' => [
                                        '@type' => 'EntryPoint',
                                        'urlTemplate' => $search_page_url . '{search_term_string}',
                                    ],
                                    'query-input' => 'required name=search_term_string',
                                ];
                            }
                        }
                        if ($graph_data['@type'] == 'Organization') {
                            $schema_item['@type'] = apply_filters(
                                'akka_schema_organization_schema_type',
                                'Organization'
                            );
                            if ($search_page_url = apply_filters('akka_schema_search_page_url', null)) {
                                $schema_item['potentialAction'] = [
                                    '@type' => 'SearchAction',
                                    'target' => [
                                        '@type' => 'EntryPoint',
                                        'urlTemplate' => $search_page_url . '{search_term_string}',
                                    ],
                                    'query-input' => 'required name=search_term_string',
                                ];
                            }
                            if (Resolvers::resolve_field($graph_data, 'logo')) {
                                $schema_item['logo'] = $graph_data['logo'];
                                $schema_item['logo']['@id'] =
                                    AKKA_FRONTEND_BASE . Utils::parseUrl($graph_data['logo']['@id']);
                                $schema_item['logo']['url'] = apply_filters(
                                    'akka_schema_organization_schema_logo_url',
                                    str_replace('wp-content/', 'app/', $graph_data['logo']['url'])
                                );
                                $schema_item['logo']['contentUrl'] = $schema_item['logo']['url'];
                            }
                            if (!empty(Resolvers::resolve_array_field($graph_data, 'sameAs'))) {
                                $schema_item['sameAs'] = $graph_data['sameAs'][0];
                            }
                            if ($contact_pont = apply_filters('akka_schema_organization_contact_point', null)) {
                                $schema_item['contactPoint'] = $contact_pont;
                            }
                        }
                        $seo_meta['schema'][] = $schema_item;
                    }
                }
            }
        }
        if (is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
            global $wpdb;
            $title = new AIOSEO\Plugin\Common\Meta\Title();
            $description = new AIOSEO\Plugin\Common\Meta\Description();
            $seo_meta = [
                'seo_title' => $title->getTitle($post),
                'seo_description' => $description->getDescription($post),
            ];
            $aio_seo_meta = $wpdb->get_results(
                sprintf(
                    'SELECT title, description, canonical_url, og_title, og_description, og_image_url, og_image_width, og_image_height, twitter_title, twitter_description, robots_noindex, robots_nofollow FROM ' .
                        $wpdb->prefix .
                        'aioseo_posts WHERE post_id = %d',
                    $post->ID
                )
            );
            if (!empty($aio_seo_meta)) {
                if ($aio_seo_meta[0]->canonical_url) {
                    $seo_meta['seo_canonical_url'] = $aio_seo_meta[0]->canonical_url;
                }
                if ($aio_seo_meta[0]->og_title) {
                    $seo_meta['og_title'] = $aio_seo_meta[0]->og_title;
                }
                if ($aio_seo_meta[0]->og_description) {
                    $seo_meta['og_description'] = $aio_seo_meta[0]->og_description;
                }
                if ($aio_seo_meta[0]->twitter_title) {
                    $seo_meta['twitter_title'] = $aio_seo_meta[0]->twitter_title;
                }
                if ($aio_seo_meta[0]->twitter_description) {
                    $seo_meta['twitter_description'] = $aio_seo_meta[0]->twitter_description;
                }
                if ($aio_seo_meta[0]->og_image_url) {
                    $seo_meta['seo_image_url'] = $aio_seo_meta[0]->og_image_url;
                    $seo_meta['seo_image_width'] = $aio_seo_meta[0]->og_image_width
                        ? $aio_seo_meta[0]->og_image_width
                        : 1200;
                    $seo_meta['seo_image_height'] = $aio_seo_meta[0]->og_image_height
                        ? $aio_seo_meta[0]->og_image_height
                        : 630;
                }
                $seo_meta['robots'] = [
                    'index' => $aio_seo_meta[0]->robots_noindex != '1',
                    'follow' => $aio_seo_meta[0]->robots_nofollow != '1',
                ];
            }
        }
        if (!isset($seo_meta['seo_title']) || !$seo_meta['seo_title']) {
            $seo_meta['seo_title'] = $post->post_title;
        }
        if (!isset($seo_meta['seo_description']) || !$seo_meta['seo_description']) {
            $seo_meta['seo_description'] = get_the_excerpt($post->ID);
        }
        $seo_meta['seo_description'] = apply_filters('akka_seo_description', $seo_meta['seo_description'], $post);
        if (!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) {
            $specific_seo_image_is_defined = true;
        }
        if (
            (!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) &&
            (!isset($seo_meta['seo_image_url']) || !$seo_meta['seo_image_url']) &&
            $post_thumbnail_id
        ) {
            $seo_meta['seo_image_id'] = $post_thumbnail_id;
        }
        if (isset($seo_meta['seo_image_id'])) {
            $image_src = Utils::get_attachment_image_src($seo_meta['seo_image_id'], 'large');
            $seo_meta['seo_image_url'] = $image_src[0];
            $seo_meta['seo_image_width'] = $image_src[1];
            $seo_meta['seo_image_height'] = $image_src[2];
        }
        if (!isset($seo_meta['og_title']) || !$seo_meta['og_title']) {
            $seo_meta['og_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['og_description']) || !$seo_meta['og_description']) {
            $seo_meta['og_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['twitter_title']) || !$seo_meta['twitter_title']) {
            $seo_meta['twitter_title'] = $seo_meta['seo_title'];
        }
        if (!isset($seo_meta['twitter_description']) || !$seo_meta['twitter_description']) {
            $seo_meta['twitter_description'] = $seo_meta['seo_description'];
        }
        if (!isset($seo_meta['canonical_url']) || !$seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = wp_get_canonical_url($post->ID);
        }
        if (isset($seo_meta['canonical_url']) && $seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = rtrim(
                str_replace(WP_HOME, AKKA_FRONTEND_BASE, $seo_meta['canonical_url']),
                '/'
            );
        }
        $seo_meta['published_date'] = get_the_date('c', $post->ID);
        $seo_meta['modified_date'] = get_the_modified_date('c', $post->ID);
        if (isset($seo_meta['seo_image_url']) && strpos($seo_meta['seo_image_url'], '/') === 0) {
            $seo_meta['seo_image_url'] = WP_HOME . $seo_meta['seo_image_url'];
        }
        foreach (['seo_title', 'og_title', 'twitter_title'] as $title_key) {
            if (isset($seo_meta[$title_key])) {
                $seo_meta[$title_key] = str_replace(
                    ['&shy;', '&ndash;', '&amp;'],
                    ['', '-', '&'],
                    $seo_meta[$title_key]
                );
            }
        }
        return apply_filters('akka_post_seo_meta', $seo_meta, $post, $specific_seo_image_is_defined);
    }
}
