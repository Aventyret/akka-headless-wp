import { render } from '@wordpress/element';

import FieldGroup from './components/FieldGroup';

window.akka = window.akka || {};

window.akka.registerFieldGroup = (groupName, fields) => {
  render(<FieldGroup metaFields={JSON.parse(fields)} />, document.getElementById(`akka_meta_${groupName}`));
};
