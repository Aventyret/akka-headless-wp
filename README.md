# Akka Headless WP

This plugin enables running headless Wordpress will the full power
of the Wordpress block editor.

## Installation

Add this repository to you composer.json:

```

  "repositories": [
    ...
    {
      "type": "vcs",
      "url": "https://github.com/Aventyret/akka-headless-wp.git"
    }
```

Install the plugin:

```
composer require aventyret/akka-headless-wp
```

Activate the plugin in your wp admin.

These environment variables are used by the plugin:

```
AKKA_CMS_COOKIE_NAME
AKKA_CMS_COOKIE_PATH
AKKA_CMS_MEDIA_BUCKET_HOSTNAME
AKKA_CMS_MEDIA_BUCKET_PORT
AKKA_CMS_MEDIA_BUCKET_PROTOCOL
AKKA_CMS_URL_INTERNAL
AKKA_FRONTEND_FLUSH_CACHE_KEY
AKKA_FRONTEND_URL
AKKA_FRONTEND_URL_INTERNAL
```


## Theme

[Akka Headless WP Sage 10 starter theme](https://github.com/Aventyret/akka-headless-wp/tree/main/starter-theme)
