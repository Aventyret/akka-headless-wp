import { render } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

import { TextControl } from '@wordpress/components';

export default function FieldGroup({ metaFields }) {
  const { editPost } = useDispatch('core/editor');

  const postMeta = useSelect(function (select) {
    return select('core/editor').getEditedPostAttribute('meta') || {};
  }, []);

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
        <>
          {metaField.type === 'text' && (
            <TextControl
              key={metaField.name}
              value={postMeta[`_${metaField.name}`]}
              label={metaField.label}
              onChange={changeFieldFn(metaField.name)}
            />
          )}
        </>
      ))}
    </>
  );
}
