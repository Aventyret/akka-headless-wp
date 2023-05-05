import { domReady } from "@roots/sage/client";
import {
  registerBlockStyle,
  unregisterBlockStyle,
  unregisterBlockVariation,
} from "@wordpress/blocks";

import setupAcf from "./acf";
import registerImageAndTextBlock from "./blocks/image-and-text-block";
import registerInfoboxBlock from "./blocks/infobox-block";
import registerBlurbsHorizontalBlock from "./blocks/blurbs-horizontal-block";
import registerBlurbBlock from "./blocks/blurb-block";

/**
 * editor.main
 */
const main = (err) => {
  if (err) {
    // handle hmr errors
    console.error(err);
  }

  registerBlockStyle("core/button", {
    name: "primary",
    label: "Primary",
    isDefault: true,
  });

  registerBlockStyle("core/button", {
    name: "secondary",
    label: "Secondary",
  });

  setupAcf();

  registerBlockStyle("core/paragraph", {
    name: "preamble",
    label: "Preamble",
  });

  registerBlockStyle("core/paragraph", {
    name: "fineprint",
    label: "Fineprint",
  });

  unregisterBlockVariation("core/image", "rounded");

  window.setTimeout(() => {
    unregisterBlockStyle("core/button", "fill");
    unregisterBlockStyle("core/button", "outline");
    unregisterBlockVariation("core/group", "group-row");
    unregisterBlockVariation("core/image", "rounded");
  }, 100);
};

/**
 * Initialize
 *
 * @see https://webpack.js.org/api/hot-module-replacement
 */
domReady(main);
registerImageAndTextBlock();
registerInfoboxBlock();
registerBlurbsHorizontalBlock();
registerBlurbBlock();

import.meta.webpackHot?.accept(main);
