const { TextControl } = wp.components;
const { useSelect, useDispatch } = wp.data;

export default function FieldGroup({ metaFields }) {
  const postMeta = useSelect(function (select) {
    return select('core/editor').getEditedPostAttribute('meta') || {};
  }, []);

  console.log(postMeta);
  const { editPost } = useDispatch('core/editor');
  function changeFieldFn(name) {
    return (value) => {
      editPost({
        meta: {
          ['_' + name]: value
        }
      });
    };
  }

  return (
    <>
      {metaFields.map((metaField) => (
        <div key={metaField.name} className="akka-field">
          {metaField.type === 'text' && (
            <TextControl
              value={postMeta[`_${metaField.name}`] || ''}
              label={metaField.label}
              onChange={changeFieldFn(metaField.name)}
            />
          )}
        </div>
      ))}
    </>
  );
}
