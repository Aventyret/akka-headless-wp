<?php

class Akka_headless_wp_content {
  public static function can_get_content() {
    return true;
  }

  public static function get_site_meta() {

    $menu_ids = get_nav_menu_locations();
    $navigation = [];

    foreach($menu_ids ? : [] as $menu_slug => $menu_id) {
      $menu_items = wp_get_nav_menu_items($menu_id);
      $menu = get_term( $menu_id );
      if ($menu_items) {
        $navigation[$menu_slug] = array_map(function($item) {
          return [
            'id' => $item->ID,
            'parent_id' => $item->menu_item_parent ? $item->menu_item_parent : NULL,
            'url' => Akka_headless_wp_utils::parseUrl($item->url),
            'title' => $item->title,
            'children' => [],
          ];
        }, $menu_items);

        $navigation[$menu_slug] = array_reduce($navigation[$menu_slug], function($menu_items, $menu_item) {
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
        }, []);
      }
    }
    $site_meta = [
      'navigation' => $navigation
    ];
    return apply_filters('ahw_site_meta', $site_meta);
  }

  private static function get_post_types_with_archives() {
    $post_types = [
      'post'
    ];
    return apply_filters('ahw_post_types_with_archives', $post_types);
  }

  private static function get_post_type_archive_permalink($post_type) {
    return ltrim(Akka_headless_wp_utils::parseUrl(get_post_type_archive_link($post_type)), '/');
  }

  public static function get_post($data) {
    $permalink = Akka_headless_wp_utils::getRouteParam($data, 'permalink');

    if (!$permalink) {
      return new WP_REST_Response(array('message' => 'Missing permalink'), 400);
    }

    $permalink = urldecode($permalink);

    $post_id = $permalink == '/' ? get_option('page_on_front') : url_to_postid($permalink);

    // Is this a post type archive?
    if (!$post_id) {
      $post_type_archive_data = self::get_post_type_archive_data($permalink);
      if ($post_type_archive_data) {
        return $post_type_archive_data;
      }
    }

    // Is this a taxonomy term archive?
    if (!$post_id) {
      $year = Akka_headless_wp_utils::getQueryParam('year', NULL);
      $taxonomy_term_archive_data = self::get_taxonomy_term_archive_data($permalink, $year);
      if ($taxonomy_term_archive_data) {
        return $taxonomy_term_archive_data;
      }
    }

    // Try polylang translated front page
    if (!$post_id && function_exists('pll_home_url') && strlen($permalink) == 2) {
      $front_page_translations = pll_get_post_translations(get_option('page_on_front'));
      if (isset($front_page_translations[$permalink])) {
        $post_id = $front_page_translations[$permalink];
      }
    }

    if (!$post_id) {
      $post_id = apply_filters("ahw_post_not_found_post_id", $post_id, $permalink);
    }

    if (!$post_id) {
      return new WP_REST_Response(array('message' => 'Post not found'), 404);
    }

    return self::get_post_data($post_id);
  }

  public static function get_posts($data) {
    $post_types = explode(',', Akka_headless_wp_utils::getQueryParam('post_type', 'post'));
    $per_page = Akka_headless_wp_utils::getQueryParam('per_page', -1);
    $offset = Akka_headless_wp_utils::getQueryParam('offset', 0);

    $query_args = [
      'offset' => $offset,
      'post_type' => $post_types,
      'posts_per_page' => $per_page,
      'ignore_sticky_posts' => 1,
    ];

    $page = Akka_headless_wp_utils::getQueryParam('page', 1);
    $query = self::get_posts_query($query_args, [
      'page' => $page,
    ]);

    $posts = self::parse_posts($query->posts);

    $post_type_object = get_post_type_object($archive_post_type);

    return [
      'count' => $query->found_posts,
      'pages' => $query->max_num_pages,
      'posts' => $posts,
      'next_page' => $query->max_num_pages > $page + 1 ? '/' . self::get_post_type_archive_permalink($post_type) . '?page=' . ($page + 1) : NULL,
    ];
  }

  public static function get_post_by_id($data) {
    $post_id = Akka_headless_wp_utils::getRouteParam($data, 'post_id');

    if (!$post_id) {
      return new WP_REST_Response(array('message' => 'Post not found'), 404);
    }

    return self::get_post_data($post_id, ['publish', 'draft', 'pending']);
  }

  public static function get_attachment_by_id($data) {
    $attachment_id = Akka_headless_wp_utils::getRouteParam($data, 'attachment_id');

    if (!$attachment_id) {
      return new WP_REST_Response(array('message' => 'Attachment not found'), 404);
    }

    $attachment_attributes = wp_get_attachment_image_src($attachment_id);

    if (!$attachment_attributes) {
      return new WP_REST_Response(array('message' => 'Attachment not found'), 404);
    }

    return [
      "attachment_id" => $attachment_id,
      "src" => AKKA_CMS_INTERNAL_BASE . $attachment_attributes[0],
      "width" => isset($attachment_attributes[1]) ? $attachment_attributes[1] : NULL,
      "height" => isset($attachment_attributes[2]) ? $attachment_attributes[2] : NULL,
    ];
  }

  private static function get_post_data($post_id, $post_status = 'publish') {
    global $post;
    $posts = get_posts([
      'post__in' => [$post_id],
      'post_type' => 'any',
      'post_status' => $post_status,
    ]);
    if (empty($posts)) {
      return new WP_REST_Response(array('message' => 'Post not found'), 404);
    }
    $post = $posts[0];

    $post_thumbnail_id = get_post_thumbnail_id($post_id);
    $featured_image_attributes = $post_thumbnail_id ? Akka_headless_wp_utils::internal_img_attributes($post_thumbnail_id, [
      'priority' => true,
    ]) : NULL;

    $post_content = str_replace('<!-- wp:fof/external-ad', '<!-- wp:fof/disabled-external-ad', $post->post_content);
    $post_content = apply_filters('the_content', $post_content);

    $data = [
      'post_id' => $post_id,
      'post_date' => get_the_date("Y-m-d", $post_id),
      'post_title' => $post->post_title,
      'post_type' => $post->post_type,
      'post_parent_id' => $post->post_parent,
      'page_template' => Akka_headless_wp_utils::get_page_template_slug($post),
      'post_content' => $post_content,
      'featured_image' => $featured_image_attributes,
      'thumbnail_caption' => get_the_post_thumbnail_caption($post_id),
      'permalink' => str_replace(WP_HOME, '', get_permalink($post_id)),
      'taxonomy_terms' => self::get_post_terms($post),
      'fields' => get_fields($post_id),
      'seo_meta' => self::get_post_seo_meta($post, $post_thumbnail_id),
    ];
    foreach(['category', 'post_tag'] as $taxonomy_slug) {
      if (isset($data['taxonomy_terms'][$taxonomy_slug])) {
        $data['primary_' . str_replace('post_tag', 'tag', $taxonomy_slug)] = $data['taxonomy_terms'][$taxonomy_slug]['primary_term'];
      }
    }

    $data = apply_filters('ahw_post_data', $data);
    unset($data['fields']);
    return $data;
  }

  private static function get_post_type_archive_data($permalink) {
    $archive_post_type = NULL;
    foreach(self::get_post_types_with_archives() as $post_type) {
      if ($permalink == self::get_post_type_archive_permalink($post_type)) {
        $archive_post_type = $post_type;
      }
    }
    if (!$archive_post_type) {
      return NULL;
    }

    $query_args = [
      'post_type' => $archive_post_type,
    ];

    $page = Akka_headless_wp_utils::getQueryParam('page', 1);
    $query = self::get_posts_query($query_args, [
      'page' => $page,
    ]);

    $posts = self::parse_posts($query->posts);

    $post_type_object = get_post_type_object($archive_post_type);

    $post_type_data = [
      'post_type' => 'post_type',
      'slug' => $archive_post_type,
      'name' => $post_type_object->label,
      'count' => $query->found_posts,
      'pages' => $query->max_num_pages,
      'posts' => $posts,
      'next_page' => $query->max_num_pages > $page + 1 ? '/' . self::get_post_type_archive_permalink($post_type) . '?page=' . ($page + 1) : NULL,
    ];

    if ($archive_post_type == 'post') {
      $post_type_data = array_merge(self::get_post_data(get_option('page_for_posts')), $post_type_data);
    }

    return apply_filters('ahw_post_type_data', $post_type_data);
  }

  private static function get_taxonomy_term_archive_data($permalink, $year = NULL) {
    $archive_taxonomy = NULL;
    foreach(get_taxonomies([], 'objects') as $taxonomy) {
      if($taxonomy->rewrite && isset($taxonomy->rewrite['slug']) && str_starts_with($permalink, $taxonomy->rewrite['slug'] . '/')) {
        $archive_taxonomy = $taxonomy;
      }
    }
    if (!$archive_taxonomy) {
      return NULL;
    }
    $archive_taxonomy_term = get_term_by('slug', substr($permalink, strpos($permalink, '/') + 1), $archive_taxonomy->name);
    if (!$archive_taxonomy_term) {
      return NULL;
    }

    $query_args = [
      'post_type' => $archive_taxonomy->object_type,
      'tax_query' => [
        [
          'taxonomy' => $archive_taxonomy->name,
          'field' => 'slug',
          'terms' => $archive_taxonomy_term->slug
        ],
      ],
    ];
    if ($year) {
      $query_args["date_query"] = [
        "year" => $year,
      ];
    }

    $page = Akka_headless_wp_utils::getQueryParam('page', 1);
    $query = self::get_posts_query($query_args, [
      'page' => $page,
    ]);

    $posts = self::parse_posts($query->posts);

    $taxonomy_term_data = [
      'post_type' => 'taxonomy_term',
      'taxonomy' => $archive_taxonomy->name,
      'taxonomy_label' => $archive_taxonomy->labels->singular_name,
      'term_id' => $archive_taxonomy_term->term_id,
      'slug' => $archive_taxonomy_term->slug,
      'name' => $archive_taxonomy_term->name,
      'count' => $query->found_posts,
      'pages' => $query->max_num_pages,
      'posts' => $posts,
      'pages' => $query->max_num_pages,
      'next_page' => $query->max_num_pages > $page + 1 ? '/' . get_term_link($archive_taxonomy_term->term_id) . '?page=' . ($page + 1) : NULL,
    ];

    return apply_filters('ahw_taxonomy_term_data', $taxonomy_term_data);
  }

  private static function get_post_terms($post) {
    $post_type_taxonomy_map = apply_filters('ahw_headless_post_type_taxonomy_map', [
      'post' => ['category', 'post_tag'],
    ]);
    $taxonomies = isset($post_type_taxonomy_map[$post->post_type]) ? $post_type_taxonomy_map[$post->post_type] : [];
    return array_reduce($taxonomies, function($terms, $taxonomy_slug) use ($post) {
      $taxonomy = get_taxonomy($taxonomy_slug);
      $taxonomy_terms = get_the_terms($post, $taxonomy_slug);
      $terms[$taxonomy_slug] = [
        'taxonomy' => [
          'name' => $taxonomy->label,
          'name_singular' => $taxonomy->labels->singular_name,
          'slug' => $taxonomy_slug,
        ],
        'terms' => array_map(function($term) {
          return [
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'url' => \Akka_headless_wp_utils::parseUrl(get_term_link($term->term_id)),
          ];
        }, $taxonomy_terms ? $taxonomy_terms : []),
      ];
      $terms[$taxonomy_slug]['primary_term'] = self::get_primary_term($taxonomy_slug, $terms[$taxonomy_slug]['terms']);
      return $terms;
    }, []);
  }

  public static function get_term($data) {
    $taxonomy_slug = str_replace('-', '_', Akka_headless_wp_utils::getRouteParam($data, 'taxonomy_slug'));
    $term_slug = Akka_headless_wp_utils::getRouteParam($data, 'term_slug');

    $term = get_term_by('slug', $term_slug, $taxonomy_slug);

    if (!$term) {
      return new WP_REST_Response(array('message' => 'Term not found'), 404);
    }

    $query_args = [
      'tax_query' => [
        [
          'taxonomy' => $taxonomy_slug,
          'field' => 'slug',
          'terms' => $term_slug
        ]
      ],
    ];

    $page = Akka_headless_wp_utils::getQueryParam('page', 1);
    $query = self::get_posts_query($query_args, [
      'page' => $page,
    ]);

    $posts_html = self::get_posts_html($query->posts);

    return [
      'term_id' => $term->term_id,
      'slug' => $term->slug,
      'name' => $term->name,
      'count' => $term->count,
      'pages' => $query->max_num_pages,
      'posts' => $posts_html,
      'next_page' => '/term/' . $taxonomy_slug . '/' . $term->slug . '/' . ($page + 1),
    ];
  }

  public static function get_author($data) {

    $author_slug = isset($data['author_slug']) ? $data['author_slug'] : false;

    if (!$author_slug) {
      return new WP_REST_Response(array('message' => 'Missing author slug'), 400);
    }

    $author_slug = urldecode($author_slug);

    $author = get_user_by('slug', $author_slug);

    if (!$author) {
      return new WP_REST_Response(array('message' => 'Author not found'), 404);
    }

    $query_args = [
      'author' => $author->data->ID,
      'offset' => $offset,
    ];

    $query = self::get_posts_query($query_args, [
      'page' => Akka_headless_wp_utils::getQueryParam('page', 1),
    ]);

    $posts_html = self::get_posts_html($query->posts);

    return [
      'author_id' => $author->data->ID,
      'slug' => $author_slug,
      'name' => $author->data->user_nicename,
      'count' => $query->found_posts,
      'pages' => $query->max_num_pages,
      'posts' => $posts_html,
    ];
  }

  private static function get_posts_query($query_args, $options = []) {
    if (isset($options['page']) && $options['page'] > 1) {
      $query_args = self::set_offset_and_per_page($query_args, $options['page']);
    }

    if (isset($query_args['s']) && function_exists('relevanssi_do_query')) {
      return self::get_relevanssi_query($query_args);
    }

    $query = new WP_Query($query_args);

    // Recalculate max_num_pages if there is an offset
    if (isset($query_args["offset"]) && $query_args["offset"] > 0) {
      $query->max_num_pages = ceil(max( 0, $query->found_posts - $query_args["offset"] ) / $query_args["posts_per_page"]);
    }

    return $query;
  }

  private static function get_relevanssi_query($query_args) {
    $query = new WP_Query();
    $query->parse_query($query_args);
    relevanssi_do_query( $query );

    return $query;
  }

  private static function set_offset_and_per_page($query_args, $page = 1) {
    if (!isset($query_args['posts_per_page'])) {
      $posts_per_page = get_option('posts_per_page');
      $query_args['posts_per_page'] = $posts_per_page;
    }
    if (!isset($query_args['offset'])) {
      $query_args['offset'] = 0;
    }
    $query_args['offset'] += ((int)$page - 1) * $query_args['posts_per_page'];
    return $query_args;
  }

  private static function get_posts_html($posts) {
    $html = \Roots\view('components.blurbs', [
      'posts' => $posts,
      'size' => 'S',
      'gridcolclassname' => 'grid__col--M--4',
      'attributes' => new Illuminate\View\ComponentAttributeBag([])
    ])->render();
    $html = Akka_headless_wp_utils::replaceHrefs($html);
    $html = Akka_headless_wp_utils::replaceSrcs($html);

    return Akka_headless_wp_utils::replaceHrefs($html);
  }

  private static function parse_posts($posts) {
    $post_datas = array_map(function($post) {
      if (is_array($post)) {
        return $post;
      }
      return self::get_post_in_archive($post);
    }, $posts);
    return $post_datas;
  }

  public static function get_post_in_archive($post) {
    $thumbnail_id = get_post_thumbnail_id(
        $post->ID
    );
    $thumbnail_attributes = $thumbnail_id
        ? \Akka_headless_wp_utils::internal_img_attributes(
            $thumbnail_id,
            [
                "size" => apply_filters('awh_post_in_archive_image_size', "full"),
            ]
        )
        : null;

    $category_terms = get_the_category($post->ID);
    $categories = !empty($category_terms)
      ? array_map(function ($category) {
          return [
              "id" => $category->term_id,
              "name" => $category->name,
              "slug" => $category->slug,
              "url" => \Akka_headless_wp_utils::parseUrl(get_term_link($category->term_id)),
          ];
      }, $category_terms)
      : [];
    $categories = array_filter($categories, function($category) {
      return !in_array($category['slug'], ['uncategorized', 'okategoriserad']);
    });
    $tag_terms = get_the_tags($post->ID);
    $tags = !empty($tag_terms)
      ? array_map(function ($tag) {
          return [
              "id" => $tag->term_id,
              "name" => $tag->name,
              "slug" => $tag->slug,
              "url" => \Akka_headless_wp_utils::parseUrl(get_term_link($tag->term_id)),
          ];
      }, $tag_terms)
      : [];

    $post_in_archive = [
      "post_id" => $post->ID,
      "post_date" => get_the_date("Y-m-d", $post->ID),
      "url" => Akka_headless_wp_utils::parseUrl(get_permalink($post->ID)),
      "image_id" => $thumbnail_id,
      "image_src" => !empty($thumbnail_attributes)
          ? $thumbnail_attributes["src"]
          : null,
      "image_width" => !empty($thumbnail_attributes)
          ? $thumbnail_attributes["width"]
          : null,
      "image_height" => !empty($thumbnail_attributes)
          ? $thumbnail_attributes["height"]
          : null,
      "image_alt" => "",
      "title" => $post->post_title,
      "post_type" => $post->post_type,
      "description" => get_the_excerpt($post->ID),
      "categories" => $categories,
      "primary_category" => self::get_primary_term('category', $categories),
      "tags" => $tags,
      "primary_tag" => self::get_primary_term('post_tag', $tags),
    ];
    return apply_filters('awh_post_in_archive', $post_in_archive, $post);
  }

  public static function search($data) {
    $query = urldecode(Akka_headless_wp_utils::getRouteParam($data, 'query'));

    if (empty($query) || strlen($query) < 2) {
      return [
        'count' => 0,
        'pages' => 0,
        'posts' => [],
      ];
    }

    $query_args = [
      's' => $query,
    ];

    $page = Akka_headless_wp_utils::getQueryParam('page', 1);
    $query = self::get_posts_query($query_args, [
      'page' => $page,
    ]);

    $posts = self::parse_posts($query->posts);

    $search_result_data = [
      'count' => $query->found_posts,
      'pages' => $query->max_num_pages,
      'posts' => $posts,
    ];

    return apply_filters('ahw_search_result_data', $search_result_data);
  }

  private static function get_post_seo_meta($post, $post_thumbnail_id = NULL) {
    $seo_meta = [
    ];
    $specific_seo_image_is_defined = FALSE;
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
      foreach($seo_fields as $seo_attr => $meta_key) {
        $meta_value = get_post_meta($post->ID, $meta_key, true);
        if ($meta_value) {
          $seo_meta[$seo_attr] = $meta_value;
        }
      }
    }
    if (is_plugin_active('wordpress-seo/wp-seo.php')) {
      $yoast_class = YoastSEO()->classes->get(Yoast\WP\SEO\Surfaces\Meta_Surface::class);
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
    }
    if (!isset($seo_meta['seo_title']) || !$seo_meta['seo_title']) {
      $seo_meta['seo_title'] = $post->post_title;
    }
    if (!isset($seo_meta['seo_description']) || !$seo_meta['seo_description']) {
      $seo_meta['seo_description'] = get_the_excerpt($post->ID);
    }
    if (!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) {
      $specific_seo_image_is_defined = TRUE;
    }
    if ((!isset($seo_meta['seo_image_id']) || !$seo_meta['seo_image_id']) && $post_thumbnail_id) {
      $seo_meta['seo_image_id'] = $post_thumbnail_id;
    }
    if (isset($seo_meta['seo_image_id'])) {
      $image_src = wp_get_attachment_image_src($seo_meta['seo_image_id'], 'large');
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
    $seo_meta['canonical_url'] = wp_get_canonical_url($post->ID);
    $seo_meta['published_date'] = get_the_date('c', $post->ID);
    $seo_meta['modified_date'] = get_the_modified_date('c', $post->ID);
    if (isset($seo_meta['seo_image_url']) && strpos($seo_meta['seo_image_url'], '/') === 0) {
      $seo_meta['seo_image_url'] = WP_HOME . $seo_meta['seo_image_url'];
    }
    foreach(["seo_title", "og_title", "twitter_title"] as $title_key) {
      $seo_meta[$title_key] = str_replace(['&shy;', '&ndash;'], ['', '-'], $seo_meta[$title_key]);
    }
    return apply_filters("ahw_seo_meta", $seo_meta, $post, $specific_seo_image_is_defined);
  }

  private static function get_term_seo_meta($term) {
    $seo_meta = [
    ];
    if (function_exists('the_seo_framework')) {
      $seo_meta_serialized = get_post_meta($post_id, 'autodescription-term-settings', true);
      if ($seo_meta_serialized) {
        $seo_meta_unserialized = unserialize($seo_meta_serialized);
        $seo_fields = [
          'seo_title' => 'doctitle',
          'seo_description' => 'description',
          'seo_image_url' => 'social_image_url',
          'og_title' => 'og_title',
          'og_description' => 'og_description',
          'twitter_title' => 'tw_title',
          'twitter_description' => 'tw_description',
        ];
        foreach($seo_fields as $seo_attr => $meta_key) {
          if (isset($seo_meta_unserialized[$meta_key]) && $seo_meta_unserialized[$meta_key]) {
            $seo_meta[$seo_attr] = $seo_meta_unserialized[$meta_key];
          }
        }
      }
    }
    if (!isset($seo_meta['seo_title'])) {
      $seo_meta['seo_title'] = $term->name;
    }
    if (!isset($seo_meta['seo_description'])) {
      $seo_meta['seo_description'] = $term->description;
    }
    if (!isset($seo_meta['og_title'])) {
      $seo_meta['og_title'] = $seo_meta['seo_title'];
    }
    if (!isset($seo_meta['og_description'])) {
      $seo_meta['og_description'] = $seo_meta['seo_description'];
    }
    if (!isset($seo_meta['twitter_title'])) {
      $seo_meta['twitter_title'] = $seo_meta['seo_title'];
    }
    if (!isset($seo_meta['twitter_description'])) {
      $seo_meta['twitter_description'] = $seo_meta['seo_description'];
    }
    if (isset($seo_meta['seo_image_url']) && strpos($seo_meta['seo_image_url'], '/') === 0) {
      $seo_meta['seo_image_url'] = WP_HOME . $seo_meta['seo_image_url'];
    }
    return $seo_meta;
  }

  private static function get_primary_term($taxonomy, $terms) {
    if (empty($terms)) {
      return NULL;
    }
    if (count($terms) > 1 && function_exists('yoast_get_primary_term_id')) {
      $term_id = yoast_get_primary_term_id($taxonomy);
      $term_index = array_search($term_id, array_column($terms, 'id'));
      if ($term_index !== FALSE) {
        return $terms[$term_index];
      }
    }
    return $terms[0];
  }
}
