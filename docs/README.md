# Plugin Documentation

Akka is a stack and convention for building headless wordpress sites. This plugin is the core of the Wordpress part of the stack.

Check out the [api](api/README.md) for comprehensive documentation.

## Key concepts

### Wordpress conventions

The plugin together with the [Akka Bas theme](https://github.com/Aventyret/akka-bas-theme) comes with a set of customizable wordpress conventions that are meant to facilitate building performant, maintainable and editor friendly Wordpress solutions. Settings that are provided by the plugin are typically customizable through filters that the plugin exposes.

### Routing

The plugin exposes a set of rest endpoints, the main idea is that the frontend application should use the Wordpress settings for routing, so that posts, term archives and post type archives are accessed in the frontend by their permalink. This routing is handled by the `Akka\Router` class.

### Post types and posts

The plugin exposes an api to register custom post types and to configure settings and ACF fields for these as well as for Worpress' core post types. This is done through the `Akka\PostTypes` class.

Akka has it's own post abstractions, 'Post Single' and 'Post Blurb'. Single is a representation of a post as a full page, while blurb is a representation of a post as a small preview, typically used in a list or grid. The Akka plugin will provide these objects through it's rest endpoints, and theme developers can adjust the data scheme of these objects through [Filters](api/hooks/README.md).

### Taxonomies and terms

The plugin exposes an api to register custom taxonomies and to configure settings for these and ACF fields for their terms as well as for Wordpress' core taxonomies. This is done through the `Akka\Taxonomies` class.

Similarly to posts Akka has it's own abstractions for taxonomy terms ('Term'). These Term objects can also be adjusted by theme depelopers through filters.

### The block editor

The Akka solution relies heavily on the block editor. The plugins rest endpoints return the contents of the block editor (post_content) as HTML to the frontend application. The frontend parses the HTML to jsx and renders it. For core blocks this "just works" unless there is any client side interactivity required (for cases that require client side interactivity we advise against using core blocks). For custom blocks Akka has a concept called Akka Blocks, that are blocks that are rendered server side in Wordpress and pass props to a react component in the frontend application. The Akka Blocks concept also includes an editor component called `AkkaServerSideRender` that is used to render the block in the editor in a similar way as the Wordpress `ServerSideRender` component, but for akka block HTML that is shown in the editor is acutally rendered by the frontend application. Akka blocks are registered through the `Akka\AkkaBlocks` class.

### Akka Plugins

Some Wordpress features of Akka are shiped in separate plugins:

- Akka Multilang: https://github.com/Aventyret/akka-headless-wp-multilang
- Akka Forms: https://github.com/Aventyret/akka-headless-wp-forms
- Akka Redirects: https://github.com/Aventyret/akka-headless-wp-redirects

### Third party plugins

Akka solutions are typically built using a set of plugins that are meant to be used together. Some of these plugins are required and some are not. Typically any plugins that function in Wordpress Admin can be used in addition to these, but any plugins that alter the frontend output of a web site, such as plugins that provide custom forms, will not work as expected out of the box. 

Supported third pary plugins are:

- Advanced Custom Fields: https://www.advancedcustomfields.com/
- Solarplexus: https://wordpress.org/plugins/solarplexus/
- Yoast: https://wordpress.org/plugins/wordpress-seo/
- SEO Framework: https://wordpress.org/plugins/autodescription/
- All in One SEO: https://wordpress.org/plugins/all-in-one-seo-pack/
- Disable Comments: https://wordpress.org/plugins/disable-comments/
- Duplicate Post: https://wordpress.org/plugins/duplicate-post/
- Relevanssi: https://wordpress.org/plugins/relevanssi/
