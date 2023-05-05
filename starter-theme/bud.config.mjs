// @ts-check

/**
 * Build configuration
 *
 * @see {@link https://bud.js.org/guides/configure}
 * @param {import('@roots/bud').Bud} app
 */
export default async (app) => {
  app
    /**
     * Application entrypoints
     */
    .entry({
      editor: ["@scripts/editor", "@styles/editor"],
    })

    /**
     * Directory contents to be included in the compilation
     */
    .assets(["images"])

    /**
     * Matched files trigger a page reload when modified
     */
    .watch(["resources/views/**/*", "app/**/*"])

    /**
     * Proxy origin (`WP_HOME`)
     */
    .proxy("https://cms.akka.test")

    /**
     * Development origin
     */
    .serve("http://0.0.0.0:3000")

    /**
     * URI of the `public` directory
     */
    .setPublicPath("/app/themes/akka-headless-starter-theme/public/")

    /**
     * Generate WordPress `theme.json`
     *
     * @note This overwrites `theme.json` on every build.
     */
    .wpjson
      .settings({
        color: {
          custom: false,
          customGradient: false,
          defaultPalette: false,
          defaultGradients: false,
          palette: [
          ],
        },
        custom: {
          spacing: {},
          typography: {
            'font-size': {},
            'line-height': {},
          },
        },
        spacing: {
          padding: false,
          units: ['px', '%', 'em', 'rem', 'vw', 'vh'],
        },
        layout: {
          contentSize: "1280px",
          wideSize: "1200px"
        },
        typography: {
          customFontSize: false,
          dropCap: true,
          fluid: false,
          fontFamilies: [],
          fontSizes: [],
          fontStyle: false,
          fontWeight: false,
          letterSpacing: false,
          lineHeight: false,
          textDecoration: false,
          textTransform: false,
        },
        blocks: {
          'core/button': {
            'border': {
              'radius': false
            },
            'color':{
              'text': false,
              'background': false,
              'customDuotone': false,
              'customGradient': false,
              'defaultDuotone': false,
              'defaultGradients': false,
              'duotone': [],
              'gradients': [],
              'palette': []
            },
          }
        },
      })
      .enable()
};
