const { createRoot } = wp.element;

// import setupFieldgroups from './field-groups';
import setupBlockStyles from './block-styles';
import setupVariations from './variations';
import setupAcf from './acf';

window.akka = window.akka || {};

// setupFieldgroups();
setupBlockStyles();
setupVariations();
setupAcf();
