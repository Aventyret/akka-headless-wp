const { createRoot } = wp.element;

import FieldGroup from './components/FieldGroup';

window.akka = window.akka || {};

window.akka.registerFieldGroup = (groupName, fields) => {
  window.setTimeout(() => {
    // NOTE: 2024-10-22: this triggers a warning but should be okay https://github.com/WordPress/gutenberg/issues/62923#issuecomment-2199438175
    const root = createRoot(document.getElementById(`akka_meta_${groupName}`));
    root.render(<FieldGroup metaFields={JSON.parse(fields)} />);
  }, 200);
};
