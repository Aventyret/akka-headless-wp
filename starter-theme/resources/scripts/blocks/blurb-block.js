import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  useBlockProps,
  MediaUpload,
} from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  TextControl,
  TextareaControl,
} from "@wordpress/components";
import { withSelect, select } from "@wordpress/data";

function className({ size }) {
  return `Blurb Blurb--nolink Blurb--${size}`;
}

export default function () {
  registerBlockType("akka/blurb", {
    title: "Blurb",
    icon: "testimonial",
    category: "layout",
    attributes: {
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
      alt: {
        type: "string",
        default: "",
      },
      size: {
        type: "string",
        default: "medium",
      },
      title: {
        type: "string",
        default: "",
      },
      description: {
        type: "string",
        default: "",
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
      let { alt, size, title, description, mediaUrl, mediaWidth, mediaHeight } =
        props.attributes;

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
          <div {...blockProps} className="Blurb-list-horizontal__list-item">
            <div {...blockProps} className={className(props.attributes)}>
              {mediaUrl && (
                <figure className="Blurb__image-container">
                  <img
                    src={mediaUrl}
                    width={mediaWidth}
                    height={mediaHeight}
                    alt={alt}
                  />
                </figure>
              )}
              {(title || description || !mediaUrl) && (
                <div className="Blurb__content">
                  {title && <h3 className="Blurb__title">{title}</h3>}
                  {description && (
                    <div className="Blurb__description">{description}</div>
                  )}
                  {!title && !description && !mediaUrl && (
                    <div className="Blurb__description">
                      <em>Add content for this blurb in the sidebar.</em>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
          <InspectorControls>
            <PanelBody title="Settings">
              <SelectControl
                label="Reverse columns on large screens"
                value={size}
                onChange={(value) => setAttributes({ size: value })}
                options={[
                  {
                    value: "small",
                    label: "Small",
                  },
                  {
                    value: "medium",
                    label: "Medium",
                  },
                ]}
              />
            </PanelBody>
            <PanelBody title="Content">
              <fieldset className="components-placeholder__fieldset">
                {hasMedia && (
                  <legend className="components-placeholder__instructions">
                    {media.title.rendered} (
                    <a href="#" onClick={removeMedia}>
                      remove
                    </a>
                    )
                  </legend>
                )}
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
              <br />
              {hasMedia && (
                <TextControl
                  label="Alt text"
                  value={alt}
                  onChange={(value) => setAttributes({ alt: value })}
                />
              )}
              <TextControl
                label="Title"
                value={title}
                onChange={(value) => setAttributes({ title: value })}
              />
              <TextareaControl
                label="Text"
                value={description}
                onChange={(value) => setAttributes({ description: value })}
              />
            </PanelBody>
          </InspectorControls>
        </>
      );
    }),
    save: (props) => {
      const blockProps = useBlockProps.save();
      let { size, title, description, mediaUrl, mediaWidth, mediaHeight } =
        props.attributes;

      return (
        <li {...blockProps} className="Blurb-list-horizontal__list-item">
          <div {...blockProps} className={className(props.attributes)}>
            {mediaUrl && (
              <figure className="Blurb__image-container">
                <img
                  src={mediaUrl}
                  width={mediaWidth}
                  height={mediaHeight}
                  alt=""
                />
              </figure>
            )}
            <div className="Blurb__content">
              {title && <h3 className="Blurb__title">{title}</h3>}
              {description && (
                <div className="Blurb__description">{description}</div>
              )}
            </div>
          </div>
        </li>
      );
    },
  });
}
