/**
 * Mock Data Helper for Widgets
 *
 * This utility provides a simple interface for widgets to load mock data
 * when the mock data mode is enabled.
 */

export default {
  /**
   * Load data for a widget - either mock or real data
   * @param {Object} store - Vuex store instance
   * @param {String} widgetName - Name of the widget (matches JSON filename)
   * @param {Function} realDataLoader - Async function to load real data
   * @returns {Promise} - Resolves with either mock or real data
   */
  async loadWidgetData(store, widgetName, realDataLoader) {
    if (store.state.mockDataEnabled) {
      console.log(
        `[Mock Data] Loading mock data for ${widgetName} (${store.state.mockDataScenario})`,
      );
      try {
        const mockData = await store.dispatch("loadMockData", {
          widgetName,
          scenario: store.state.mockDataScenario,
        });

        if (mockData) {
          console.log(
            `[Mock Data] Successfully loaded mock data for ${widgetName}`,
          );
          return { success: true, data: mockData, isMockData: true };
        } else {
          console.warn(
            `[Mock Data] No mock data found for ${widgetName}, falling back to real data`,
          );
        }
      } catch (error) {
        console.error(
          `[Mock Data] Error loading mock data for ${widgetName}:`,
          error,
        );
        console.log(`[Mock Data] Falling back to real data`);
      }
    }

    // Load real data
    if (realDataLoader) {
      return await realDataLoader();
    }

    return { success: false, error: "No data loader provided" };
  },

  /**
   * Check if mock data is enabled
   * @param {Object} store - Vuex store instance
   * @returns {Boolean}
   */
  isMockDataEnabled(store) {
    return store.state.mockDataEnabled;
  },

  /**
   * Get current mock data scenario
   * @param {Object} store - Vuex store instance
   * @returns {String}
   */
  getMockDataScenario(store) {
    return store.state.mockDataScenario;
  },

  /**
   * Base path for mock data files
   */
  BASE_PATH: "../data/mock-data/",
};
