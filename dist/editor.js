(() => {
  // src/components/FieldGroup.jsx
  var { TextControl } = wp.components;
  var { useSelect, useDispatch } = wp.data;
  function FieldGroup({ metaFields }) {
    const postMeta = useSelect(function(select) {
      return select("core/editor").getEditedPostAttribute("meta") || {};
    }, []);
    const { editPost } = useDispatch("core/editor");
    function changeFieldFn(name) {
      return (value) => {
        editPost({
          meta: {
            ["_" + name]: value
          }
        });
      };
    }
    return /* @__PURE__ */ React.createElement(React.Fragment, null, metaFields.map((metaField) => /* @__PURE__ */ React.createElement("div", { key: metaField.name, className: "akka-field" }, metaField.type === "text" && /* @__PURE__ */ React.createElement(
      TextControl,
      {
        value: postMeta[`_${metaField.name}`] || "",
        label: metaField.label,
        onChange: changeFieldFn(metaField.name)
      }
    ))));
  }

  // src/field-groups.js
  var { createRoot } = wp.element;
  function field_groups_default() {
    window.akka.registerFieldGroup = (groupName, fields) => {
      window.setTimeout(() => {
        const root = createRoot(document.getElementById(`akka_meta_${groupName}`));
        root.render(/* @__PURE__ */ React.createElement(FieldGroup, { metaFields: JSON.parse(fields) }));
      }, 200);
    };
  }

  // src/block-styles.js
  var defaultBlockStylesToUnregister = [
    {
      block: "core/button",
      style: "fill"
    },
    {
      block: "core/button",
      style: "outline"
    },
    {
      block: "core/button",
      style: "width"
    },
    {
      block: "core/group",
      style: "group-row"
    },
    {
      block: "core/group",
      style: "group-stack"
    },
    {
      block: "core/image",
      style: "rounded"
    },
    {
      block: "core/separator",
      style: "default"
    },
    {
      block: "core/separator",
      style: "wide"
    },
    {
      block: "core/separator",
      style: "dots"
    }
  ];
  function allowBlockStyle(blockStyle) {
    if (window.akka.coreBlockStyles) {
      return window.akka.coreBlockStyles.find((b) => b.block === blockStyle.block && b.style === blockStyle.style);
    }
    return false;
  }
  function unregisterBlockStyles() {
    defaultBlockStylesToUnregister.filter((blockStyle) => {
      return !allowBlockStyle(blockStyle);
    }).forEach((blockStyle) => {
      wp.blocks.unregisterBlockStyle(blockStyle.block, blockStyle.style);
    });
  }
  function block_styles_default() {
    window.document.addEventListener("DOMContentLoaded", function() {
      window.setTimeout(unregisterBlockStyles, 100);
      window.setTimeout(unregisterBlockStyles, 150);
      window.setTimeout(unregisterBlockStyles, 200);
    });
  }

  // src/variations.js
  var defaultVariationsToUnregister = [
    {
      block: "core/embed",
      variation: "amazon-kindle"
    },
    {
      block: "core/embed",
      variation: "animoto"
    },
    {
      block: "core/embed",
      variation: "bluesky"
    },
    {
      block: "core/embed",
      variation: "cloudup"
    },
    {
      block: "core/embed",
      variation: "collegehumor"
    },
    {
      block: "core/embed",
      variation: "crowdsignal"
    },
    {
      block: "core/embed",
      variation: "dailymotion"
    },
    {
      block: "core/embed",
      variation: "flickr"
    },
    {
      block: "core/embed",
      variation: "imgur"
    },
    {
      block: "core/embed",
      variation: "issuu"
    },
    {
      block: "core/embed",
      variation: "kickstarter"
    },
    {
      block: "core/embed",
      variation: "meetup-com"
    },
    {
      block: "core/embed",
      variation: "mixcloud"
    },
    {
      block: "core/embed",
      variation: "pinterest"
    },
    {
      block: "core/embed",
      variation: "pocket-casts"
    },
    {
      block: "core/embed",
      variation: "reddit"
    },
    {
      block: "core/embed",
      variation: "reverbnation"
    },
    {
      block: "core/embed",
      variation: "screencast"
    },
    {
      block: "core/embed",
      variation: "scribd"
    },
    {
      block: "core/embed",
      variation: "slideshare"
    },
    {
      block: "core/embed",
      variation: "smugmug"
    },
    {
      block: "core/embed",
      variation: "soundcloud"
    },
    {
      block: "core/embed",
      variation: "speaker-deck"
    },
    {
      block: "core/embed",
      variation: "spotify"
    },
    {
      block: "core/embed",
      variation: "ted"
    },
    {
      block: "core/embed",
      variation: "tumblr"
    },
    {
      block: "core/embed",
      variation: "videopress"
    },
    {
      block: "core/embed",
      variation: "tiktok"
    },
    {
      block: "core/embed",
      variation: "twitter"
    },
    {
      block: "core/embed",
      variation: "vimeo"
    },
    {
      block: "core/embed",
      variation: "wolfram-cloud"
    },
    {
      block: "core/embed",
      variation: "wordpress"
    },
    {
      block: "core/embed",
      variation: "wordpress-tv"
    },
    {
      block: "core/embed",
      variation: "youtube"
    }
  ];
  function allowVariation(variation) {
    if (window.akka.coreBlockVariations) {
      return window.akka.coreBlockVariations.find(
        (v) => v.block === variation.block && v.variation === variation.variation
      );
    }
    return false;
  }
  function unregisterVariations() {
    defaultVariationsToUnregister.filter((variation) => {
      return !allowVariation(variation);
    }).forEach((variation) => {
      wp.blocks.unregisterBlockVariation(variation.block, variation.variation);
    });
  }
  function variations_default() {
    window.document.addEventListener("DOMContentLoaded", function() {
      window.setTimeout(unregisterVariations, 100);
      window.setTimeout(unregisterVariations, 150);
      window.setTimeout(unregisterVariations, 200);
    });
  }

  // src/acf.js
  function acf_default() {
    if (!window.acf) {
      return;
    }
    window.acf.addFilter("color_picker_args", function(args) {
      const settings = wp.data.select("core/editor").getEditorSettings();
      let colors = settings.colors.map((themeColor) => themeColor.color);
      args.palettes = colors;
      return args;
    });
  }

  // src/editor.js
  var { createRoot: createRoot2 } = wp.element;
  window.akka = window.akka || {};
  field_groups_default();
  block_styles_default();
  variations_default();
  acf_default();
})();
