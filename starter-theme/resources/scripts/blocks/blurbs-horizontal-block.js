import { registerBlockType } from "@wordpress/blocks";
import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
} from "@wordpress/block-editor";
import { PanelBody, SelectControl } from "@wordpress/components";

const ALLOWED_BLOCKS = ["akka/blurb"];

function className({ columns }) {
  return `Blurb-list-horizontal Blurb-list-horizontal--columns-${columns}`;
}

export default function () {
  registerBlockType("akka/blurbs-horizontal", {
    title: "Blurbs",
    icon: "grid-view",
    category: "layout",
    attributes: {
      columns: {
        type: "string",
        default: "3",
      },
    },
    edit: (props) => {
      const blockProps = useBlockProps();
      const { setAttributes } = props;
      let { columns } = props.attributes;

      return (
        <>
          <div className={className(props.attributes)}>
            <div className="Blurb-list-horizontal__list" data-editor="true">
              <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} />
            </div>
          </div>
          <InspectorControls>
            <PanelBody title="Settings">
              <SelectControl
                label="Number of columns to display"
                value={columns}
                onChange={(value) => setAttributes({ columns: value })}
                options={[
                  {
                    value: "3",
                    label: "3",
                  },
                  {
                    value: "4",
                    label: "4",
                  },
                ]}
              />
            </PanelBody>
          </InspectorControls>
        </>
      );
    },
    save: (props) => {
      const blockProps = useBlockProps.save();

      return (
        <div {...blockProps} className={className(props.attributes)}>
          <ul className="Blurb-list-horizontal__list">
            <InnerBlocks.Content />
          </ul>
        </div>
      );
    },
  });
}
