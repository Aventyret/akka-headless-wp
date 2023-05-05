import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  InnerBlocks,
  useBlockProps,
  MediaUpload,
} from "@wordpress/block-editor";
import { PanelBody, CheckboxControl } from "@wordpress/components";
import { withSelect, select } from "@wordpress/data";

const ALLOWED_BLOCKS = ["core/paragraph", "core/list"];

function getClassName({ reverseColumns }) {
  let className = "Image-and-text grid";
  if (reverseColumns) {
    className += " Image-and-text--reverse";
  }
  return className;
}

export default function () {
  registerBlockType("akka/image-and-text", {
    title: "Image + text",
    icon: "align-pull-left",
    category: "layout",
    attributes: {
      reverseColumns: {
        type: "boolean",
        default: false,
      },
      mediaId: {
        type: "number",
        default: 0,
      },
      mediaUrl: {
        type: "string",
        default: "",
      },
      mediaHeight: {
        type: "number",
        default: 0,
      },
      mediaWidth: {
        type: "number",
        default: 0,
      },
    },
    edit: withSelect((select, props) => {
      return {
        media: props.attributes.mediaId
          ? select("core").getMedia(props.attributes.mediaId)
          : undefined,
      };
    })((props) => {
      const blockProps = useBlockProps();
      const { setAttributes, media } = props;
      let {
        reverseColumns,
        colorScheme,
        customBgColor,
        hasPaddingTop,
        hasPaddingBottom,
      } = props.attributes;

      const removeMedia = (e) => {
        e.preventDefault();
        setAttributes({
          mediaId: 0,
          mediaUrl: "",
          mediaHeight: 0,
          mediaWidth: 0,
        });
      };
      const hasMedia = typeof media !== "undefined";

      return (
        <>
          <div {...blockProps} className={getClassName(props.attributes)}>
            <div className="grid__col grid__col--S--6">
              {hasMedia ? (
                <figure className="Image-and-text__image">
                  <img src={media.source_url} alt="" />
                </figure>
              ) : null}
              <fieldset className="components-placeholder__fieldset">
                <legend className="components-placeholder__instructions">
                  {hasMedia ? (
                    <>
                      {media.title.rendered} (
                      <a href="#" onClick={removeMedia}>
                        remove
                      </a>
                      )
                    </>
                  ) : (
                    <>
                      Upload an image or select an existing image from the media
                      library.
                    </>
                  )}
                </legend>
                <div className="components-form-file-upload">
                  <MediaUpload
                    onSelect={(uploadedMedia) => {
                      setAttributes({
                        mediaId: uploadedMedia.id,
                        mediaUrl: uploadedMedia.url,
                        mediaHeight: uploadedMedia.height,
                        mediaWidth: uploadedMedia.width,
                      });
                    }}
                    allowedTypes={["image"]}
                    multiple={false}
                    render={({ open }) => (
                      <>
                        <button
                          type="button"
                          className="components-button is-primary"
                          onClick={open}
                        >
                          {hasMedia ? "Change image" : "Add image"}
                        </button>
                      </>
                    )}
                  />
                </div>
              </fieldset>
            </div>
            <div className="grid__col grid__col--S--6">
              <div className="Image-and-text__text">
                <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} />
              </div>
            </div>
          </div>
          <InspectorControls>
            <PanelBody title="Layout">
              <CheckboxControl
                label="Reverse columns on large screens"
                checked={reverseColumns}
                onChange={(isChecked) =>
                  setAttributes({ reverseColumns: isChecked })
                }
              />
            </PanelBody>
          </InspectorControls>
        </>
      );
    }),
    save: (props) => {
      const blockProps = useBlockProps.save();

      return (
        <div {...blockProps} className={getClassName(props.attributes)}>
          <div className="grid__col grid__col--12 grid__col--S--2 grid__col--M--3 grid__col--L--5">
            {props.attributes.mediaUrl ? (
              <figure className="Image-and-text__image">
                <img
                  src={props.attributes.mediaUrl}
                  width={props.attributes.mediaWidth}
                  height={props.attributes.mediaHeight}
                  alt=""
                />
              </figure>
            ) : null}
          </div>
          <div className="grid__col grid__col--12 grid__col--S--2 grid__col--M--3 grid__col--L--5">
            <div className="Image-and-text__text">
              <InnerBlocks.Content />
            </div>
          </div>
        </div>
      );
    },
  });
}
