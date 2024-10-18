import { useCallback, useMemo } from 'react';
import isHotkey from 'is-hotkey';
import { Editable, withReact, useSlate, Slate } from 'slate-react';
import { Editor, Transforms, createEditor, Element as SlateElement } from 'slate';
import { withHistory } from 'slate-history';

const HOTKEYS = {
  'mod+b': 'bold',
  'mod+i': 'italic',
  'mod+u': 'underline'
};

export default function HtmlControl({ label, placeholder, onChange }) {
  const renderElement = useCallback((props) => <Element {...props} />, []);
  const renderLeaf = useCallback((props) => <Leaf {...props} />, []);
  const editor = useMemo(() => withHistory(withReact(createEditor())), []);
  return (
    <Slate editor={editor} onChange={onChange}>
      <div>
        <MarkButton format="bold" icon="b" />
        <MarkButton format="italic" icon="i" />
        <MarkButton format="underline" icon="u" />
        <BlockButton format="heading-two" icon="h2" />
        <BlockButton format="heading-three" icon="h3" />
      </div>
      <Editable
        renderElement={renderElement}
        renderLeaf={renderLeaf}
        placeholder={placeholder}
        spellCheck
        onKeyDown={(event) => {
          for (const hotkey in HOTKEYS) {
            if (isHotkey(hotkey, event)) {
              event.preventDefault();
              const mark = HOTKEYS[hotkey];
              toggleMark(editor, mark);
            }
          }
        }}
      />
    </Slate>
  );
}
const toggleBlock = (editor, format) => {
  const isActive = isBlockActive(editor, format, 'type');
  Transforms.unwrapNodes(editor, {
    match: (n) => !Editor.isEditor(n) && SlateElement.isElement(n),
    split: true
  });
  let newProperties = {
    type: isActive ? 'paragraph' : format
  };
  Transforms.setNodes(editor, newProperties);
};
const toggleMark = (editor, format) => {
  const isActive = isMarkActive(editor, format);
  if (isActive) {
    Editor.removeMark(editor, format);
  } else {
    Editor.addMark(editor, format, true);
  }
};
const isBlockActive = (editor, format, blockType = 'type') => {
  const { selection } = editor;
  if (!selection) return false;
  const [match] = Array.from(
    Editor.nodes(editor, {
      at: Editor.unhangRange(editor, selection),
      match: (n) => !Editor.isEditor(n) && SlateElement.isElement(n) && n[blockType] === format
    })
  );
  return !!match;
};
const isMarkActive = (editor, format) => {
  const marks = Editor.marks(editor);
  return marks ? marks[format] === true : false;
};
const Element = ({ attributes, children, element }) => {
  const style = { textAlign: element.align };
  switch (element.type) {
    case 'block-quote':
      return (
        <blockquote style={style} {...attributes}>
          {children}
        </blockquote>
      );
    case 'bulleted-list':
      return (
        <ul style={style} {...attributes}>
          {children}
        </ul>
      );
    case 'heading-one':
      return (
        <h1 style={style} {...attributes}>
          {children}
        </h1>
      );
    case 'heading-two':
      return (
        <h2 style={style} {...attributes}>
          {children}
        </h2>
      );
    case 'list-item':
      return (
        <li style={style} {...attributes}>
          {children}
        </li>
      );
    case 'numbered-list':
      return (
        <ol style={style} {...attributes}>
          {children}
        </ol>
      );
    default:
      return (
        <p style={style} {...attributes}>
          {children}
        </p>
      );
  }
};
const Leaf = ({ attributes, children, leaf }) => {
  if (leaf.bold) {
    children = <strong>{children}</strong>;
  }
  if (leaf.code) {
    children = <code>{children}</code>;
  }
  if (leaf.italic) {
    children = <em>{children}</em>;
  }
  if (leaf.underline) {
    children = <u>{children}</u>;
  }
  return <span {...attributes}>{children}</span>;
};
const BlockButton = ({ format, icon }) => {
  const editor = useSlate();
  return (
    <button
      active={isBlockActive(editor, format, 'type')}
      onMouseDown={(event) => {
        event.preventDefault();
        toggleBlock(editor, format);
      }}
    >
      {icon}
    </button>
  );
};
const MarkButton = ({ format, icon }) => {
  const editor = useSlate();
  return (
    <button
      active={isMarkActive(editor, format)}
      onMouseDown={(event) => {
        event.preventDefault();
        toggleMark(editor, format);
      }}
    >
      {icon}
    </button>
  );
};
