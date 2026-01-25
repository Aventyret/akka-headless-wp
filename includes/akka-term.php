<?php
namespace Akka;

class Term
{
    public static function get_single_terms($post)
    {
        $post_type_taxonomy_map = apply_filters('akka_post_type_taxonomy_map', [
            'post' => ['category', 'post_tag'],
        ]);
        $taxonomies = isset($post_type_taxonomy_map[$post->post_type]) ? $post_type_taxonomy_map[$post->post_type] : [];
        return self::get_post_terms($post, $taxonomies);
    }

    public static function get_blurb_terms($post)
    {
        $post_type_taxonomy_map = apply_filters('akka_blurb_post_type_taxonomy_map', []);
        $taxonomies = isset($post_type_taxonomy_map[$post->post_type]) ? $post_type_taxonomy_map[$post->post_type] : [];
        return self::get_post_terms($post, $taxonomies);
    }

    private static function get_post_terms($post, $taxonomies)
    {
        return array_reduce(
            $taxonomies,
            function ($terms, $taxonomy_slug) use ($post) {
                $taxonomy = get_taxonomy($taxonomy_slug);
                $taxonomy_terms = get_the_terms($post, $taxonomy_slug);
                $terms[$taxonomy_slug] = [
                    'taxonomy' => [
                        'name' => $taxonomy->label,
                        'name_singular' => $taxonomy->labels->singular_name,
                        'slug' => $taxonomy_slug,
                    ],
                    'terms' => array_map(
                        function ($term) use ($taxonomy_slug, $taxonomy) {
                            $term_url = self::get_url($term->term_id);
                            return apply_filters(
                                'akka_post_term',
                                [
                                    'term_id' => $term->term_id,
                                    'parent_id' => $term->parent,
                                    'name' => $term->name,
                                    'slug' => $term->slug,
                                    'url' => apply_filters('akka_term_url', $term_url, $term, $taxonomy),
                                ],
                                $taxonomy_slug
                            );
                        },
                        $taxonomy_terms ? $taxonomy_terms : []
                    ),
                ];
                $terms[$taxonomy_slug]['primary_term'] = self::get_primary_term(
                    $taxonomy_slug,
                    $terms[$taxonomy_slug]['terms'],
                    $post
                );
                return $terms;
            },
            []
        );
    }

    public static function get_terms($taxonomy_slug)
    {
        $taxonomy_terms = get_terms(['taxonomy' => $taxonomy_slug, 'hide_empty' => false]);
        return array_map(
            function ($term) {
                $term_url = self::get_url($term->term_id);
                return [
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'url' => apply_filters('akka_term_url', $term_url, $term, $taxonomy),
                ];
            },
            $taxonomy_terms ? $taxonomy_terms : []
        );
    }

    public static function get_url($term_id)
    {
        return Utils::parse_url(str_replace(WP_HOME, '', get_term_link($term_id)));
    }

    private static function get_seo_meta($term_data)
    {
        $seo_meta = [];
        $specific_seo_image_is_defined = false;
        $specific_seo_description_is_defined = false;
        if (function_exists('the_seo_framework')) {
            // TODO: Support SEO framework
        }
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $yoast_class = YoastSEO()->classes->get(Yoast\WP\SEO\Surfaces\Meta_Surface::class);
            $yoast_meta = $yoast_class->for_term($term_data['term_id']);
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
                $specific_seo_image_is_defined = true;
            }
        }
        if (!isset($seo_meta['seo_title']) || !$seo_meta['seo_title']) {
            $seo_meta['seo_title'] = $term_data['post_title'];
        }
        if (!isset($seo_meta['seo_description']) || !$seo_meta['seo_description']) {
            $seo_meta['seo_description'] = term_description($term_data['term_id']);
            $specific_seo_description_is_defined = true;
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
            $seo_meta['canonical_url'] = Term::get_url($term_data['term_id']);
        }
        if (isset($seo_meta['canonical_url']) && $seo_meta['canonical_url']) {
            $seo_meta['canonical_url'] = rtrim(
                str_replace(WP_HOME, AKKA_FRONTEND_BASE, $seo_meta['canonical_url']),
                '/'
            );
        }
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
        return apply_filters(
            'akka_term_seo_meta',
            $seo_meta,
            $term_data,
            $specific_seo_image_is_defined,
            $specific_seo_description_is_defined
        );
    }

    private static function get_primary_term($taxonomy, $terms, $post)
    {
        if (empty($terms)) {
            return null;
        }
        if (count($terms) > 1 && function_exists('yoast_get_primary_term_id')) {
            $term_id_column_key = apply_filters('akka_primary_term_id_column_key', 'id');
            $term_id = yoast_get_primary_term_id($taxonomy, $post);
            $term_index = array_search($term_id, array_column($terms, $term_id_column_key));
            if ($term_index !== false) {
                return $terms[$term_index];
            }
        }
        return $terms[0];
    }
}
