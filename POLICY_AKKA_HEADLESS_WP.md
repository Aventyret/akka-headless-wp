# POLICY_AKKA_HEADLESS_WP

Denna fil beskriver policy och riktlinjer för användning av `akka-headless-wp` i våra projekt. Dokumentet är avsett för både AI-agenter och utvecklare.

## 1. Syfte

`akka-headless-wp` är en WordPress-plugin designad för att driva "Akka-sajter" – headless WordPress-lösningar där Gutenberg används som den primära innehållsleverantören. 

Syftet med pluginet är att:
- Tillhandahålla ett robust REST API (`akka/v2`) för frontend-applikationer.
- Förenkla registrering av post types, taxonomier och metafält.
- Hantera headless-specifik logik såsom bildsökvägar, interna länkar och preview-url:er.
- Möjliggöra full användning av Block Editor (Gutenberg) i en headless-kontext.

## 2. Användning

### Installation
Lägg till i `composer.json`:
```json
"repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Aventyret/akka-headless-wp.git"
    }
]
```
Kör: `composer require aventyret/akka-headless-wp`

### Miljövariabler
Följande miljövariabler styr pluginets beteende och måste konfigureras:

- `AKKA_FRONTEND_URL`: URL till frontend-applikationen (t.ex. `https://example.com`).
- `AKKA_FRONTEND_URL_INTERNAL`: Intern URL till frontend (används av server-side calls).
- `AKKA_CMS_URL_INTERNAL`: Intern URL till CMS (t.ex. `http://localhost:8080`).
- `AKKA_CMS_COOKIE_NAME`: Namn på auth-cookie (default: `cms_signed_in`).
- `AKKA_CMS_COOKIE_PATH`: Path för auth-cookie.
- `AKKA_FRONTEND_FLUSH_CACHE_KEY`: Nyckel för att rensa frontend-cache.
- `AKKA_FRONTEND_FLUSH_CACHE_ENDPOINT`: Endpoint för cache-rensning.

### Tema
Använd `Akka Bas theme` som boilerplate för temat.

## 3. API:er

Pluginet exponerar ett REST API under `akka/v2`.

### Endpoints
- **GET** `/akka/v2/site_meta`: Hämtar global sidmetadata.
- **GET** `/akka/v2/post/{permalink}`: Hämtar en post baserat på permalink.
- **GET** `/akka/v2/posts`: Hämtar en lista av poster.
- **GET** `/akka/v2/post_by_id/{post_id}`: Hämtar en specifik post via ID.
- **GET** `/akka/v2/attachment_by_id/{attachment_id}`: Hämtar media via ID.
- **GET** `/akka/v2/search/{query}`: Söker efter innehåll.
- **POST** `/akka/v2/editor/block`: Renderar ett block (kräver inloggning).

## 4. WP Filter

Följande filter kan användas för att modifiera pluginets beteende:

- `akka_post_types_with_archives`: Lägg till post types som ska ha arkivsidor.
- `akka_allowed_blocks`: Modifiera listan över tillåtna Gutenberg-block per post type.
- `akka_post_type_taxonomy_map`: Mappa taxonomier till post types.
- `akka_blurb_post_type_taxonomy_map`: Mappa taxonomier till "blurbs" (kortfattat innehåll).
- `akka_post_parse_url`: Modifiera URL-parsning i `Utils::parseUrl`.
- `akka_post_replace_hrefs`: Modifiera hur länkar ersätts i innehållet.
- `akka_img_attributes`: Modifiera bildattribut som returneras av API:t.

## 5. WP Hooks (Actions)

### Egna Actions
- `akka_cache_flushed`: Körs efter att en begäran om cache-rensning har skickats till frontend.

### Core Hooks som används
- `save_post` / `acf/save_post`: Triggar cache-rensning på frontend.
- `template_redirect`: Omdirigerar besökare till frontend-URL:en.
- `wp_login` / `wp_logout`: Hanterar `AKKA_CMS_COOKIE_NAME`.

## 6. Klasser / Hjälp klasser

Dessa klasser ska användas för att registrera innehållsstrukturer. Detta säkerställer att data exponeras korrekt i API:t.

### `Akka\PostTypes`
Används för att registrera Custom Post Types.
- `register_post_type($slug, $args, $options)`: Registrerar CPT.
    - `$options`: 
        - `meta_groups`: Array av metafältsgrupper.
        - `acf_field_groups`: Array av ACF-fält.
        - `allowed_core_blocks`: Specifika core-block att tillåta.
        - `blocks_template`: Default block-template.

### `Akka\Taxonomies`
Används för att registrera taxonomier.
- `register_taxonomy($slug, $args, $options)`: Registrerar taxonomi.
    - `$options`:
        - `post_types`: Vilka post types den gäller för.
        - `has_archive`: Om den har en arkivsida.

### `Akka\MetaFields`
Registrerar native WP post meta med typning.
- `register_post_meta_field($group, $fields, $options)`

### `Akka\Utils`
Innehåller hjälpfunktioner.
- `isHeadless()`: Kontrollerar om requesten är ett REST-anrop.
- `external_img_src($id)`: Hämtar absolut URL till bild.
- `replaceHrefs($content)`: Justerar interna länkar för headless.

## 7. Vanliga tasks

### Skapa ny Post Type
Använd `Akka\PostTypes::register_post_type` istället för `register_post_type`.

```php
use Akka\PostTypes;

PostTypes::register_post_type('campaign', 
    [
        'label' => 'Kampanj', // Automatisk generering av labels
        'hierarchical' => false,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'public' => true
    ],
    [
        'allowed_core_blocks' => ['core/paragraph', 'core/heading'],
        'meta_groups' => [
            [
                'group' => ['name' => 'campaign_meta', 'label' => 'Inställningar'],
                'fields' => [
                    ['name' => 'start_date', 'label' => 'Startdatum', 'type' => 'text']
                ]
            ]
        ]
    ]
);
```

### Skapa ny Taxonomi
Använd `Akka\Taxonomies::register_taxonomy`.

```php
use Akka\Taxonomies;

Taxonomies::register_taxonomy('campaign_category', 
    [
        'label' => 'Kampanjkategori',
        'hierarchical' => true
    ], 
    [
        'post_types' => ['campaign'],
        'has_archive' => true
    ]
);
```

### Skapa nya Block
Block skapas normalt via ACF eller som React-block i temat/pluginet, men `akka-headless-wp` hanterar renderingen via API:t. Se till att blocken är tillåtna via `allowed_core_blocks` i post type-definitionen eller globalt filter.

## 8. Relation till Solarplexus

**Solarplexus** är namnet på vårt designsystem och frontend-ramverk. `akka-headless-wp` agerar **backend-motor** för Solarplexus-sajter.

- **Solarplexus (Frontend)**: Konsumerar data från `akka-headless-wp`. Förväntar sig data i det format som definieras av `akka/v2` API:t.
- **Akka (Backend)**: Tillhandahåller strukturerad data. Alla custom post types och fält som definieras här måste följa namngivningskonventioner som Solarplexus-frontend klarar av.

Se `POLICY_SOLARPLEXUS.md` (om tillgänglig) för specifika regler kring frontend-komponenter och hur de mappar mot backend-data.

