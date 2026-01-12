<?php
namespace Akka;

class PostTypes
{
    public static function register_post_type($post_type_slug, $args, $options = [])
    {
        $supports = self::set_supports($args);
        $args = array_merge(
            [
                'label' => null,
                'has_archive' => false,
                'public' => false,
                'exclude_from_search' => false,
                'show_ui' => true,
                'show_in_nav_menus' => true,
                'menu_icon' => 'dashicons-admin-post',
                'hierarchical' => false,
                'show_in_rest' => true,
                'menu_position' => 10,
                'labels' => [
                    'name' => Resolvers::resolve_field($args, 'label'),
                    'singular_name' => Resolvers::resolve_field($args, 'label'),
                ],
            ],
            $args
        );
        $args['supports'] = $supports;
        if (!$args['label']) {
            throw new Exception('Akka post type label missing!');
        }
        if ($args['public']) {
            $args['rewrite'] = [
                'slug' => Resolvers::resolve_field($args, 'slug') ?? Utils::stringToRoute($args['label']),
                'with_front' => false,
            ];
        }
        $options = array_merge(
            [
                'meta_groups' => [],
                'acf_field_groups' => [],
                'allowed_core_blocks' => [],
                'unallowed_core_blocks' => [],
                'blocks_template' => [],
            ],
            $options
        );
        add_action('init', function () use ($post_type_slug, $args) {
            register_post_type($post_type_slug, $args);
        });
        if ($args['has_archive']) {
            add_filter('akka_post_types_with_archives', function ($post_types) use ($post_type_slug) {
                if (!in_array($post_type_slug, $post_types)) {
                    $post_types[] = $post_type_slug;
                }
                return $post_types;
            });
        }
        foreach ($options['meta_groups'] as $meta_group) {
            MetaFields::register_post_meta_field(
                Resolvers::resolve_array_field($meta_group, 'group'),
                Resolvers::resolve_array_field($meta_group, 'fields'),
                array_merge(
                    [
                        'post_types' => [$post_type_slug],
                    ],
                    Resolvers::resolve_array_field($meta_group, 'options')
                )
            );
        }
        foreach ($options['acf_field_groups'] as $acf_field_group) {
            $acf_field_group['location'] = Resolvers::resolve_field($acf_field_group, 'location') ?? [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => $post_type_slug,
                    ],
                ],
            ];
            Acf::register_field_group($acf_field_group);
        }
        if (!empty($options['allowed_core_blocks'])) {
            add_filter(
                'akka_allowed_blocks',
                function ($blocks) use ($post_type_slug, $options) {
                    if (get_post_type() === $post_type_slug) {
                        $blocks = Blocks::add_allowed_blocks($blocks, $options['allowed_core_blocks']);
                    }
                    return $blocks;
                },
                11
            );
        }
        if (!empty($options['unallowed_core_blocks'])) {
            add_filter(
                'akka_allowed_blocks',
                function ($blocks) use ($post_type_slug, $options) {
                    if (get_post_type() === $post_type_slug) {
                        $blocks = Blocks::remove_unallowed_blocks($blocks, $options['unallowed_core_blocks']);
                    }
                    return $blocks;
                },
                11
            );
        }
        if (!empty($options['blocks_template'])) {
            self::set_post_type_blocks_template($post_type_slug, $options['blocks_template']);
        }
    }

    public static function unregister_post_post_type() {
        add_action('admin_menu', function () {
          remove_menu_page('edit.php');
        });

        add_action('admin_bar_menu', function ($wp_admin_bar) {
          $wp_admin_bar->remove_node('new-post');
        });

        add_action('admin_footer', function ($wp_admin_bar) {
          ?>
          <script type="text/javascript">
            const newPostLink = window.document.getElementById('wp-admin-bar-new-post');
            if (newPostLink) {
              newPostLink.remove();
            }
          </script>
          <?php
        });

        add_action('wp_dashboard_setup', function ($wp_admin_bar) {
          remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        });
    }

    public static function rename_post_type($post_type, $labels) {
        add_action('init', function () use ($post_type, $labels) {
            $post_type_object = get_post_type_object($post_type);
            if ($post_type_object) {
                if (isset($labels['plural'])) {
                    $post_type_object->labels->name = $labels['plural'];
                }
                if (isset($labels['singular'])) {
                    $post_type_object->labels->singular_name = $labels['singular'];
                }
                if (isset($labels['add_new'])) {
                    $post_type_object->labels->add_new = $labels['add_new'];
                }
                if (isset($labels['add_new_item'])) {
                    $post_type_object->labels->add_new_item = $labels['add_new_item'];
                }
                if (isset($labels['edit_item'])) {
                    $post_type_object->labels->edit_item = $labels['edit_item'];
                }
                if (isset($labels['new_item'])) {
                    $post_type_object->labels->new_item = $labels['new_item'];
                }
                if (isset($labels['view_item'])) {
                    $post_type_object->labels->view_item = $labels['view_item'];
                }
                if (isset($labels['search_items'])) {
                    $post_type_object->labels->search_items = $labels['search_items'];
                }
                if (isset($labels['not_found'])) {
                    $post_type_object->labels->not_found = $labels['not_found'];
                }
                if (isset($labels['not_found_in_trash'])) {
                    $post_type_object->labels->not_found_in_trash = $labels['not_found_in_trash'];
                }
                if (isset($labels['all_items'])) {
                    $post_type_object->labels->all_items = $labels['all_items'];
                }
                if (isset($labels['plural'])) {
                    $post_type_object->labels->menu_name = $labels['plural'];
                }
                if (isset($labels['name_admin_bar'])) {
                    $post_type_object->labels->name_admin_bar = $labels['name_admin_bar'];
                }
            }
        });
    }

    public static function set_post_type_blocks_template($post_type_slug, $blocks_template)
    {
        add_action('init', function () use ($post_type_slug, $blocks_template) {
            $post_type_object = get_post_type_object($post_type_slug);
            if ($post_type_object) {
                $post_type_object->template = $blocks_template;
            }
        });
    }

    private static function set_supports($args)
    {
        $default_suports = ['title', 'revisions', 'thumbnail', 'editor', 'custom-fields'];
        if (!Resolvers::resolve_field($args, 'supports')) {
            return $default_suports;
        }
        if (!is_array($args['supports'])) {
            throw new Exception('Akka post type supports should be an array!');
        }
        // Return setting in args if array of strings
        if (!empty($args['supports']) && isset($args['supports'][0])) {
            return $args['supports'];
        }
        // Merge with defaults if deep array
        $supports = $default_suports;
        foreach ($args['supports'] as $support => $enable) {
            if ($enable) {
                if (!in_array($support, $supports)) {
                    $supports[] = $support;
                }
            } else {
                if (in_array($support, $supports)) {
                    $index = array_search($support, $supports);
                    array_splice($supports, $index);
                }
            }
        }
        return $supports;
    }
}
