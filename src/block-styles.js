const defaultBlockStylesToUnregister = [
  {
    block: 'core/button',
    style: 'fill'
  },
  {
    block: 'core/button',
    style: 'outline'
  },
  {
    block: 'core/button',
    style: 'width'
  },
  {
    block: 'core/group',
    style: 'group-row'
  },
  {
    block: 'core/group',
    style: 'group-stack'
  },
  {
    block: 'core/image',
    style: 'rounded'
  },
  {
    block: 'core/separator',
    style: 'default'
  },
  {
    block: 'core/separator',
    style: 'wide'
  },
  {
    block: 'core/separator',
    style: 'dots'
  }
];

function allowBlockStyle(blockStyle) {
  if (window.akka.coreBlockStyles) {
    return window.akka.coreBlockStyles.find((b) => b.block === blockStyle.block && b.style === blockStyle.style);
  }
  return false;
}

function unregisterBlockStyles() {
  defaultBlockStylesToUnregister
    .filter((blockStyle) => {
      return !allowBlockStyle(blockStyle);
    })
    .forEach((blockStyle) => {
      wp.blocks.unregisterBlockStyle(blockStyle.block, blockStyle.style);
    });
}

export default function () {
  window.document.addEventListener('DOMContentLoaded', function () {
    window.setTimeout(unregisterBlockStyles, 100);
    window.setTimeout(unregisterBlockStyles, 150);
    window.setTimeout(unregisterBlockStyles, 200);
  });
}
