<?php
namespace Akka;

class SiteMeta
{
    public static function get_site_meta()
    {
        $menu_ids = get_nav_menu_locations();
        $navigation = [];
        $navigation_meta = [];

        foreach ($menu_ids ?: [] as $menu_slug => $menu_id) {
            $menu_id = apply_filters('akka_site_meta_menu_id', $menu_id, $menu_slug);
            $slug = $menu_slug;
            $menu_items = wp_get_nav_menu_items($menu_id);
            // Polylang fix for menus
            if (function_exists('pll_current_language') && pll_current_language() != pll_default_language()) {
                if (str_ends_with($slug, '___' . pll_current_language())) {
                    $slug = str_replace('___' . pll_current_language(), '', $slug);
                }
            }
            $navigation[$slug] = null;
            $navigation_meta[$slug] = null;
            if ($menu_items) {
                $navigation[$slug] = array_map(function ($item) {
                    return [
                        'id' => $item->ID,
                        'parent_id' => $item->menu_item_parent ? $item->menu_item_parent : null,
                        'url' => Utils::parseUrl($item->url),
                        'title' => $item->title,
                        'description' => $item->description,
                        'children' => [],
                    ];
                }, $menu_items);

                $navigation[$slug] = array_reduce(
                    $navigation[$slug],
                    function ($menu_items, $menu_item) {
                        if ($menu_item['parent_id']) {
                            $ids = array_column($menu_items, 'id');
                            $parent_index = array_search($menu_item['parent_id'], $ids);
                            if ($parent_index !== false) {
                                $menu_items[$parent_index]['children'][] = $menu_item;
                            }
                        } else {
                            $menu_items[] = $menu_item;
                        }
                        return $menu_items;
                    },
                    []
                );
                $navigation_meta[$slug] = [
                    'name' => wp_get_nav_menu_name($slug),
                ];
            }
        }
        $site_meta = array_merge(self::get_site_meta_global_fields(), [
            'navigation' => $navigation,
            'navigation_meta' => $navigation_meta,
        ]);
        if (class_exists('WPSEO_Redirect_Manager')) {
            $redirect_manager = new WPSEO_Redirect_Manager();
            $redirects = $redirect_manager->get_all_redirects();
            $site_meta['redirects'] = array_reduce(
                $redirects,
                function ($redirects, $r) {
                    $redirect = [
                        'origin' => trim($r->get_origin(), '/'),
                        'target' => rtrim($r->get_target(), '/'),
                        'status_code' => $r->get_type() ?? 301,
                    ];
                    if (!str_starts_with($redirect['target'], '/')) {
                        $redirect['target'] = '/' . $redirect['target'];
                    }
                    if (in_array($r->get_format(), ['regex', 'plain'])) {
                        $redirects[$r->get_format()][] = $redirect;
                    }
                    return $redirects;
                },
                ['plain' => [], 'regex' => []]
            );
        }
        return apply_filters('akka_site_meta', $site_meta);
    }

    private static function get_site_meta_global_fields()
    {
        $site_meta = [
            'header' => [],
        ];
        $fields = get_fields('global');
        foreach ($fields ? $fields : [] as $field => $value) {
            if (count(explode('_', $field)) > 2) {
                [$g, $section, $key] = preg_split('/_/', $field, 3, PREG_SPLIT_NO_EMPTY);
                if (isset($section) && isset($key)) {
                    if ($value instanceof \WP_Post) {
                        $permalink = Utils::parseUrl(get_permalink($value));
                        $value = [
                            'post_id' => $value->ID,
                            'permalink' => $permalink,
                            'url' => $permalink,
                            'post_title' => $value->post_title,
                            'post_name' => $value->post_name,
                        ];
                    }
                    $site_meta[$section][$key] = $value;
                }
            }
        }
        $site_meta['header']['home_url'] = Utils::parseUrl(home_url());
        $site_meta['header']['posts_url'] = Utils::parseUrl(get_permalink(get_option('page_for_posts')));
        if (isset($site_meta['cookies']) && isset($site_meta['cookies']['details_link'])) {
            $site_meta['cookies']['details_link'] = Utils::parseUrl($site_meta['cookies']['details_link']);
        }
        if (isset($site_meta['cookies']) && isset($site_meta['cookies']['enabled_cookie_types'])) {
            $site_meta['cookies']['enabled_cookie_types'] = array_reduce(
                $site_meta['cookies']['enabled_cookie_types'] ? $site_meta['cookies']['enabled_cookie_types'] : [],
                function ($enabled_types, $cookie_type) {
                    return array_merge($enabled_types, [
                        [
                            'type' => $cookie_type . '_storage',
                            'name' => get_field('global_cookies_' . $cookie_type . '_name', 'global'),
                            'read_only' => false,
                            'default_enabled' => false,
                        ],
                    ]);
                },
                [
                    [
                        'type' => 'necessary_storage',
                        'name' => get_field('global_cookies_necessary_name', 'global'),
                        'read_only' => true,
                        'default_enabled' => true,
                    ],
                ]
            );
        }
        return $site_meta;
    }
}
