# Designnot: ACF-block som andra blockkontrakt

Status: utkast för granskning (2026-10-18). Branch `feat/acf-blocks` från `main` (3.6.0).

## Bakgrund

Chef.se behåller sina cirka 33 ACF-block permanent i Next-porten. 5 725 poster bär blocken, och någon andra innehållsmigrering görs inte. I dag löser `mu-plugins/chef-akka/blocks.php` det med en `pre_render_block`-krok som gör om `acf/*` till `data-akka-component`-markörer. Den vägen lyfts in i pluginet som ett officiellt kontrakt bredvid `AkkaBlocks::register_block_type`. Chef blir första konsument, och chef-akkas kopia tas bort när stödet är släppt.

Kraven:

1. Opt-in per block. Befintliga fältscheman och lagrad `attrs.data` fungerar oförändrade.
2. Förhandsvisning i editorn renderas av Next via `/api/editor/component`, så den överlever att Blade-temat tas bort.
3. Fungerar med allow-listan i `Akka\Blocks`.

## Föreslaget API

En ny klass `Akka\AcfBlocks` i `includes/akka-acf-blocks.php`.

```php
\Akka\AcfBlocks::register_block_type('acf/read-more', [
    'akka_component_name' => 'ReadMore',          // Krävs
    'post_types' => ['post', 'book', 'event'],     // Valfri, styr allow-listan
    'block_props_callback' => function ($post_id, $props, $block) {
        return [...];                              // Valfri
    },
    'acf_block' => [                               // Valfri, se registreringslägen
        'title' => 'Läs mer',
        'category' => 'common',
        'icon' => 'info',
        'mode' => 'edit',
        'supports' => ['align' => false],
    ],
]);
```

Komponentnamnet anges alltid explicit, som i `AkkaBlocks`. Pluginet härleder inget namn ur blocknamnet. Chef kan fortsätta härleda namnen i sin egen registreringsloop.

### Två registreringslägen

Med `acf_block` registrerar pluginet blocket självt med `acf_register_block_type` på `acf/init`. `render_callback` sätts av pluginet, och `render_template` är inte tillåtet. Det är målläget och det enda läget efter chefs cutover.

Utan `acf_block` tar pluginet över ett block som någon annan redan registrerar. Det sker via filtret `acf/register_block_type_args`, som finns i ACF 5.12. Den ursprungliga `render_callback` sparas som fallback och används i alla anrop som inte är akka-REST eller editorns förhandsvisning. Det är chefs övergångsläge: Sage-temat registrerar blocken från Blade-filernas huvuden och renderar www, medan Next får markörer. Om ett block aldrig registreras loggas ett fel på `acf/init` i stället för att det tyst saknas.

## Renderingen

Pluginet äger `render_callback` för varje registrerat block. ACF har då redan kört `acf_setup_meta` för blockets `data`, så `get_fields()` ger samma värden som Blade-vyerna får i dag. Callbacken väljer väg i den här ordningen:

| Anrop | Resultat |
|---|---|
| `$is_preview` (ACF:s `acf/ajax/fetch-block` i editorn) | Props med `isEditor: true` skickas till Next `/api/editor/component`. Svarets HTML skrivs ut. |
| REST-anrop under `akka/v2` | `<div data-akka-component="X" data-akka-props="…">`, samma markör som `AkkaBlocks` |
| Övrigt, läge med `acf_block` | Markören, precis som `AkkaBlocks` gör utanför admin |
| Övrigt, övertagande-läge | Den ursprungliga `render_callback` |

Förhandsvisningen går alltså via ACF:s egen fetch-block-loop och inte via `AkkaServerSideRender` och `akka/v2/editor/block`. ACF äger redan fältformuläret, växlingen mellan edit- och preview-läge, debounce och InnerBlocks. Att byta ut den mot `AkkaServerSideRender` skulle kräva JS-registrering per block, och då är det inte längre opt-in utan ändrade scheman. Next-delen delas däremot. Anropet mot `/api/editor/component` bryts ut ur `AkkaBlocks::render_editor_block` till en gemensam metod med samma proxy-header, timeout och felhantering. Next-sidan märker ingen skillnad mellan blocktyperna.

Om Next inte svarar visar förhandsvisningen den ursprungliga callbacken när en sådan finns, annars en kort felruta i editorn. Själva redigeringen påverkas inte, eftersom fälten ligger i sidopanelen.

## Props

Props byggs i tre steg:

1. `get_fields()` normaliseras rekursivt. `WP_Post` blir en akka-blurb via `Post::post_to_blurb`. `WP_Term` blir `term_id`, `parent_id`, `name`, `slug`, `taxonomy` och `url`, samma form som termerna i en posts `terms`. Övriga värden lämnas orörda, även ACF:s bildarrayer.
2. Basnycklarna `blockId`, `className`, `anchor` och `align` läggs till från ACF:s blockarray.
3. `block_props_callback($post_id, $props, $block)` får resultatet och returnerar de slutliga propsen. Utan callback är steg 1 och 2 propsen.

InnerBlocks-HTML skickas som `children`, som i `AkkaBlocks`. Därefter körs filtret `akka_acf_block_props` med `($props, $block_name, $block, $post_id)` för tvärgående saker som chefs texturize.

Lagrad `attrs.data` läses bara, aldrig skrivs. Fältnycklar och scheman är oförändrade.

## Allow-listan

Registreringen lägger till blocket i `akka_allowed_blocks`, villkorat på `post_types` precis som `AkkaBlocks`. Med `acf_block` skickas `post_types` även till ACF, så att inserter och allow-list är överens.

Chef tar i dag bort `Akka\Blocks::allowed_blocks` helt. När alla använda ACF-block är registrerade kan den raden bort, förutsatt att chef lägger till sina övriga block (core-block utöver standardlistan, `gravityforms/form`, `splx/*`) via `akka_allowed_blocks`. Det verifierar jag mot de blocktyper som faktiskt finns i chefs innehåll.

## Chef-akkas övriga avsteg

Det här tar pluginet över:

- Allow-listan, enligt ovan.
- Grindningen av blockkoden på `is_akka_request()`. Callbacken väljer väg själv.

Det här tar pluginet inte över, eftersom det bara gäller tiden då Blade och Next lever sida vid sida:

- `redirect_to_frontend` och `the_content`-filtren. De tas bort i chef-akka fram till cutover.
- `enqueue_frontend_styles`. Den laddar `cms.css` på `enqueue_block_assets`, alltså även i Blades frontend. Efter cutover behövs den, för att Next-förhandsvisningen ska få rätt stil i editorn.

Två saker blir egna uppföljningar:

- Chef vill ha WordPress `srcset` permanent. Pluginet nollar den hårt via `max_srcset_image_width`. Ett filter eller en inställning för det är en egen ändring.
- Chefs Solarplexus 1.16 saknar `splx_block_args`, därav reservvägen i `blocks.php`. Rätt väg är att uppgradera Solarplexus och använda `register_splx_block_type`.

## Det chef behöver före cutover

Inget av detta görs i pluginet, men stödet fungerar inte utan det:

- Next-appen behöver en route `app/api/editor/component` som renderar `AkkaComponent` med `renderToString`. Chefs `www` saknar den i dag.
- Blockregistreringen ligger i Blade-filernas huvuden (`Title`, `Mode`, `PostTypes`, `SupportsInnerBlocks`). De måste skrivas om till `acf_block`-argument innan temat tas bort.
- Fältgrupperna ligger i temats `resources/assets/acf-json` (87 filer). De måste flytta till chef-akka och laddas via `acf/settings/load_json`.
- Next måste servera `/cms.css` med komponentstilarna.

## InnerBlocks i förhandsvisningen

`person-product-list` och `person-product-item` använder InnerBlocks. ACF 5.12 letar efter en `<InnerBlocks />`-tagg i förhandsvisningens HTML och ersätter den med det redigerbara området. I förhandsvisningen sätter pluginet därför `children` till `<InnerBlocks />` när blocket stöder `jsx`. Next-komponenten måste skicka strängen orörd till HTML:en. Det är den del av designen jag är minst säker på, och den testas först.

## Tester och dokumentation

Repot har inga tester och ingen CI, bara `prettier --check`. Jag föreslår att inte införa ett PHPUnit-ramverk i samma ändring. Verifieringen blir:

- `npm run format:check` och `php -l` på ändrade filer.
- I chefs lokala Docker-miljö: REST-svaret för ett urval poster som tillsammans täcker alla registrerade block jämförs mot chef-akkas nuvarande utdata. Målet är identiska props.
- Förhandsvisningen kontrolleras i editorn för ett block per läge (`edit`, `preview`, `auto`) och för ett InnerBlocks-block.

Dokumentation som uppdateras:

- `docs/api/classes/README.md`: nytt avsnitt `AcfBlocks`.
- `docs/api/hooks/README.md`: `akka_acf_block_props` under Block Filters.
- `docs/api/api-endpoints/README.md`: förhandsvisningsflödet. Dessutom rättas exemplet för `editor/block`, som anger `post_id`, `block_type` och `block_attributes` medan koden läser `postId`, `blockType` och `attributes`.
- `docs/README.md`: avsnittet om blockeditorn beskriver båda kontrakten och markören som gemensamt innehållskontrakt.
- `POLICY_AKKA_HEADLESS_WP.md` §7: när ACF-kontraktet är rätt val och när JS-block är det.
- Versionen höjs till 3.7.0.

Jag committar inte i akka-headless-wp, eftersom repots `AGENTS.md` säger att ändringarna granskas av en människa först.
