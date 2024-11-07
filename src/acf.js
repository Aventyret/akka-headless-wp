export default function () {
  if (!window.acf) {
    return;
  }
  window.acf.addFilter('color_picker_args', function (args) {
    const settings = wp.data.select('core/editor').getEditorSettings();
    let colors = settings.colors.map((themeColor) => themeColor.color);

    args.palettes = colors;
    return args;
  });
}
