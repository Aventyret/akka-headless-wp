(() => {
  // src/components/FieldGroup.jsx
  var { TextControl } = wp.components;
  var { useSelect, useDispatch } = wp.data;
  function FieldGroup({ metaFields }) {
    const postMeta = useSelect(function(select) {
      return select("core/editor").getEditedPostAttribute("meta") || {};
    }, []);
    console.log(postMeta);
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

  // src/editor.js
  var { createRoot } = wp.element;
  window.akka = window.akka || {};
  window.akka.registerFieldGroup = (groupName, fields) => {
    window.setTimeout(() => {
      const root = createRoot(document.getElementById(`akka_meta_${groupName}`));
      root.render(/* @__PURE__ */ React.createElement(FieldGroup, { metaFields: JSON.parse(fields) }));
    }, 200);
  };
})();
