/**
 * Widget Registry - Auto-discovers all widgets from the widgets/ folder.
 *
 * Each widget folder must contain:
 *   - index.js exporting: { name, w, h }
 *   - WidgetName.vue (matching the folder name)
 *
 * To add a new widget:
 *   1. Create a folder in widgets/ (e.g. MyWidget/)
 *   2. Add MyWidget.vue with the component
 *   3. Add index.js exporting { name: "Display Name", w: 6, h: 4 }
 *   That's it — the registry handles the rest.
 */

import { defineAsyncComponent } from "vue";

// Webpack require.context to auto-discover all widget index.js files (metadata only)
const metadataModules = require.context("./", true, /^\.\/[^/]+\/index\.js$/);

const widgets = [];
const asyncComponents = {};

metadataModules.keys().forEach((key, idx) => {
  // key looks like "./WidgetName/index.js"
  const folderName = key.split("/")[1];
  const meta = metadataModules(key).default;

  if (!meta) {
    console.warn(`[Widget Registry] Skipping ${folderName}: no default export`);
    return;
  }

  // Build widget definition from metadata
  const widgetDef = {
    i: String(idx + 1),
    c: folderName,
    name: meta.name || folderName,
    w: meta.w || 12,
    h: meta.h || 4,
  };

  widgets.push(widgetDef);

  // Lazy-load the .vue component (same name as folder)
  asyncComponents[folderName] = defineAsyncComponent(
    () =>
      import(
        /* webpackChunkName: "[request]" */ `./${folderName}/${folderName}.vue`
      ),
  );
});

/**
 * Returns the list of all discovered widgets with their metadata.
 * Each entry has: { i, c, name, w, h }
 */
export function getWidgetDefinitions() {
  return widgets;
}

/**
 * Returns a map of { ComponentName: AsyncComponent } for Vue component registration.
 */
export function getAsyncComponents() {
  return asyncComponents;
}
