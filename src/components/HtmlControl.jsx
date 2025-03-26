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

const isMarkActive = (editor, format) => {
  const marks = Editor.marks(editor);
  return marks ? marks[format] === true : false;
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

const toggleBlock = (editor, format) => {
  const isActive = isBlockActive(editor, format, 'type');
  
  Transforms.unwrapNodes(editor, {
    match: (n) => !Editor.isEditor(n) && SlateElement.isElement(n),
    split: true
  });
  
  const newProperties = {
    type: isActive ? 'paragraph' : format
  };
  
  Transforms.setNodes(editor, newProperties);
};

const Leaf = ({ attributes, children, leaf }) => {
  let result = children;
  
  if (leaf.bold) {
    result = <strong>{result}</strong>;
  }
  
  if (leaf.code) {
    result = <code>{result}</code>;
  }
  
  if (leaf.italic) {
    result = <em>{result}</em>;
  }
  
  if (leaf.underline) {
    result = <u>{result}</u>;
  }
  
  return <span {...attributes}>{result}</span>;
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
    case 'heading-three':
      return (
        <h3 style={style} {...attributes}>
          {children}
        </h3>
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

const BlockButton = ({ format, icon }) => {
  const editor = useSlate();
  
  return (
    <button
      aria-label={`Format as ${format}`}
      data-active={isBlockActive(editor, format, 'type')}
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
      aria-label={`Format as ${format}`}
      data-active={isMarkActive(editor, format)}
      onMouseDown={(event) => {
        event.preventDefault();
        toggleMark(editor, format);
      }}
    >
      {icon}
    </button>
  );
};

export default function HtmlControl({ label, placeholder, onChange }) {
  const renderElement = useCallback(props => <Element {...props} />, []);
  const renderLeaf = useCallback(props => <Leaf {...props} />, []);
  const editor = useMemo(() => withHistory(withReact(createEditor())), []);
  
  const handleKeyDown = useCallback(event => {
    for (const hotkey in HOTKEYS) {
      if (isHotkey(hotkey, event)) {
        event.preventDefault();
        const mark = HOTKEYS[hotkey];
        toggleMark(editor, mark);
      }
    }
  }, [editor]);
  
  return (
    <Slate editor={editor} onChange={onChange}>
      <div className="html-control-toolbar">
        <MarkButton format="bold" icon="b" />
        <MarkButton format="italic" icon="i" />
        <MarkButton format="underline" icon="u" />
        <BlockButton format="heading-two" icon="h2" />
        <BlockButton format="heading-three" icon="h3" />
      </div>
      <Editable
        className="html-control-editable"
        renderElement={renderElement}
        renderLeaf={renderLeaf}
        placeholder={placeholder}
        spellCheck
        onKeyDown={handleKeyDown}
      />
    </Slate>
  );
}