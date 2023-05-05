import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  InnerBlocks,
  useBlockProps,
} from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";

const ALLOWED_BLOCKS = [
  "core/heading",
  "core/list",
  "core/paragraph",
  "core/button",
  "core/buttons",
];

export default function () {
  registerBlockType("akka/infobox", {
    title: "Infobox",
    icon: "format-aside",
    category: "layout",
    attributes: {
      infoboxTitle: {
        type: "string",
      },
    },
    edit: (props) => {
      const blockProps = useBlockProps();
      const { setAttributes } = props;
      let { infoboxTitle } = props.attributes;

      return (
        <>
          <aside {...blockProps} className="Infobox">
            {infoboxTitle ? (
              <h2 style={{ textAlign: "center", marginBottom: "1em" }}>
                [{infoboxTitle}]
              </h2>
            ) : null}
            <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} />
          </aside>
          <InspectorControls>
            <PanelBody title="a11y">
              <TextControl
                label="Screen reader heading"
                help="If the infobox has no heading you can add one for screen readers"
                value={infoboxTitle || ""}
                onChange={(value) => setAttributes({ infoboxTitle: value })}
              />
            </PanelBody>
          </InspectorControls>
        </>
      );
    },
    save: (props) => {
      const blockProps = useBlockProps.save();

      return (
        <aside {...blockProps} className="Infobox">
          {props.attributes.infoboxTitle ? (
            <h2 className="visually-hidden">{props.attributes.infoboxTitle}</h2>
          ) : null}
          <InnerBlocks.Content />
        </aside>
      );
    },
  });
}
