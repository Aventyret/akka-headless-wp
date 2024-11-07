const defaultVariationsToUnregister = [
  {
    block: 'core/embed',
    variation: 'amazon-kindle'
  },
  {
    block: 'core/embed',
    variation: 'animoto'
  },
  {
    block: 'core/embed',
    variation: 'bluesky'
  },
  {
    block: 'core/embed',
    variation: 'cloudup'
  },
  {
    block: 'core/embed',
    variation: 'collegehumor'
  },
  {
    block: 'core/embed',
    variation: 'crowdsignal'
  },
  {
    block: 'core/embed',
    variation: 'dailymotion'
  },
  {
    block: 'core/embed',
    variation: 'flickr'
  },
  {
    block: 'core/embed',
    variation: 'imgur'
  },
  {
    block: 'core/embed',
    variation: 'issuu'
  },
  {
    block: 'core/embed',
    variation: 'kickstarter'
  },
  {
    block: 'core/embed',
    variation: 'meetup-com'
  },
  {
    block: 'core/embed',
    variation: 'mixcloud'
  },
  {
    block: 'core/embed',
    variation: 'pinterest'
  },
  {
    block: 'core/embed',
    variation: 'pocket-casts'
  },
  {
    block: 'core/embed',
    variation: 'reddit'
  },
  {
    block: 'core/embed',
    variation: 'reverbnation'
  },
  {
    block: 'core/embed',
    variation: 'screencast'
  },
  {
    block: 'core/embed',
    variation: 'scribd'
  },
  {
    block: 'core/embed',
    variation: 'slideshare'
  },
  {
    block: 'core/embed',
    variation: 'smugmug'
  },
  {
    block: 'core/embed',
    variation: 'soundcloud'
  },
  {
    block: 'core/embed',
    variation: 'speaker-deck'
  },
  {
    block: 'core/embed',
    variation: 'spotify'
  },
  {
    block: 'core/embed',
    variation: 'ted'
  },
  {
    block: 'core/embed',
    variation: 'tumblr'
  },
  {
    block: 'core/embed',
    variation: 'videopress'
  },
  {
    block: 'core/embed',
    variation: 'tiktok'
  },
  {
    block: 'core/embed',
    variation: 'twitter'
  },
  {
    block: 'core/embed',
    variation: 'vimeo'
  },
  {
    block: 'core/embed',
    variation: 'wolfram-cloud'
  },
  {
    block: 'core/embed',
    variation: 'wordpress'
  },
  {
    block: 'core/embed',
    variation: 'wordpress-tv'
  },
  {
    block: 'core/embed',
    variation: 'youtube'
  }
];

function allowVariation(variation) {
  if (window.akka.coreBlockVariations) {
    return window.akka.coreBlockVariations.find(
      (v) => v.block === variation.block && v.variation === variation.variation
    );
  }
  return false;
}

function unregisterVariations() {
  defaultVariationsToUnregister
    .filter((variation) => {
      return !allowVariation(variation);
    })
    .forEach((variation) => {
      wp.blocks.unregisterBlockVariation(variation.block, variation.variation);
    });
}

export default function () {
  window.document.addEventListener('DOMContentLoaded', function () {
    window.setTimeout(unregisterVariations, 100);
    window.setTimeout(unregisterVariations, 150);
    window.setTimeout(unregisterVariations, 200);
  });
}
