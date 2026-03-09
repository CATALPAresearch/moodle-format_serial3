<template>
  <div style="width: 100%">
    <survey-prompt></survey-prompt>
    <div class="d-flex justify-content-between">
      <h2 class="main__title">{{ strings.dashboardTitle }}</h2>
      <div v-if="isModerator" class="alert alert-success">
        Condition: {{ research_condition }}
      </div>
      <div class="d-flex justify-content-end align-items-center">
        <div class="form-group d-flex align-items-center m-0">
          <select
            v-if="editMode"
            id="addDashboardItems"
            class="form-control mr-2"
            @change="addItem($event)"
          >
            <option style="display: none">
              {{ strings.dashboardAddItem }}
            </option>
            <option
              v-for="(component, index) in filteredComponents"
              :key="index"
              :value="component.i"
            >
              {{ component.name }}
            </option>
          </select>
          <button
            v-if="editMode"
            class="btn btn-primary btn-edit"
            @click="saveDashboard"
          >
            {{ strings.save }}
          </button>
          <button
            hidden
            class="btn btn-secondary btn-edit ml-2"
            @click="recalculateHeight"
            title="Recalculate grid height"
          >
            <i class="fa fa-arrows-v"></i>
          </button>
        </div>
        <menu-bar
          @editmode="toggleEditMode"
          @widgetsUpdated="reloadWidgetConfig"
          @refreshWidgets="reloadWidgetConfig"
        ></menu-bar>
      </div>
    </div>
    <div id="widgetGrid" class="grid-stack gs-12">
      <div
        class="grid-stack-item border"
        v-for="item in layoutWithPositions"
        :key="item.i"
        :id="'widget-' + item.c"
        :gs-id="'widget-' + item.c"
        :gs-w="item.w"
        :gs-h="item.h"
        :gs-x="item.x"
        :gs-y="item.y"
      >
        <div class="grid-stack-item-content" style="overflow: auto">
          <div>
            <span
              v-if="editMode && !item.fixed"
              class="remove"
              :title="strings.dashboardRemoveItem"
              @click="removeItem(item.i)"
            >
              <i class="fa fa-close"></i>
            </span>
            <component :is="item.c"></component>
          </div>
        </div>
      </div>
    </div>
    <welcome-video
      v-show="new Date().getTime() < new Date('2024.04.31').getTime()"
    ></welcome-video>
  </div>
</template>

<script>
import { defineAsyncComponent } from "vue";
import MenuBar from "./components/MenuBar.vue";
import WelcomeVideo from "./components/WelcomeVideo.vue";
import SurveyPrompt from "./components/SurveyPrompt.vue";
import Communication from "./utils/communication";

import "gridstack/dist/gridstack.min.css";
import { GridStack } from "gridstack";
import { mapState, mapGetters, mapActions } from "vuex";

// Define async components for lazy loading
const ProgressChartAdaptive = defineAsyncComponent(
  () => import("./widgets/ProgressChartAdaptive/ProgressChartAdaptive.vue"),
);
const IndicatorDisplay = defineAsyncComponent(
  () => import("./widgets/IndicatorDisplay/IndicatorDisplay.vue"),
);
const Recommendations = defineAsyncComponent(
  () => import("./widgets/Recommendations/Recommendations.vue"),
);
const TaskList = defineAsyncComponent(
  () => import("./widgets/TaskList/TaskList.vue"),
);
const Deadlines = defineAsyncComponent(
  () => import("./widgets/Deadlines/Deadlines.vue"),
);
const CourseOverview = defineAsyncComponent(
  () => import("./widgets/CourseOverview/CourseOverview.vue"),
);
const LearningStrategies = defineAsyncComponent(
  () => import("./widgets/LearningStrategies/LearningStrategies.vue"),
);
const TeacherActivity = defineAsyncComponent(
  () => import("./widgets/TeacherActivity/TeacherActivity.vue"),
);

// DO NOT use ref/reactive for grid - Vue proxies break GridStack
let grid = null;

export default {
  components: {
    MenuBar,
    WelcomeVideo,
    SurveyPrompt,
    ProgressChartAdaptive,
    IndicatorDisplay,
    Recommendations,
    TaskList,
    Deadlines,
    CourseOverview,
    LearningStrategies,
    TeacherActivity,
  },

  data() {
    return {
      courseid: -1,
      context: {},
      logger: null,

      editMode: false,
      widgetConfig: { success: false, widgets: [], canManage: false },

      // Default layout for widgets
      grid: null,
      defaultLayout: [
        {
          w: 12,
          h: 5,
          i: "1",
          name: "Adaptiver Überblick",
          c: "ProgressChartAdaptive",
        },
        {
          w: 12,
          h: 5,
          i: "2",
          name: "Lernziele",
          c: "IndicatorDisplay",
        },
        {
          w: 6,
          h: 4,
          i: "3",
          name: "Feedback",
          c: "Recommendations",
        },
        {
          w: 3,
          h: 4,
          i: "4",
          name: "Aufgabenliste",
          c: "TaskList",
        },
        {
          w: 3,
          h: 4,
          i: "5",
          name: "Termine",
          c: "Deadlines",
        },
        {
          w: 12,
          h: 3,
          i: "6",
          name: "Kursübersicht",
          c: "CourseOverview",
        },
        {
          w: 12,
          h: 4,
          i: "7",
          name: "Lehreraktivität",
          c: "TeacherActivity",
        },
        {
          w: 6,
          h: 4,
          i: "8",
          name: "Lernstrategien",
          c: "LearningStrategies",
        },
      ],

      // All available widgets for the dropdown
      allComponents: [
        {
          i: "1",
          name: "Adaptiver Überblick",
          c: "ProgressChartAdaptive",
          w: 12,
          h: 5,
        },
        { i: "2", name: "Lernziele", c: "IndicatorDisplay", w: 12, h: 5 },
        { i: "3", name: "Feedback", c: "Recommendations", w: 6, h: 4 },
        { i: "4", name: "Aufgabenliste", c: "TaskList", w: 3, h: 4 },
        { i: "5", name: "Termine", c: "Deadlines", w: 3, h: 4 },
        { i: "6", name: "Kursübersicht", c: "CourseOverview", w: 12, h: 3 },
        { i: "7", name: "Lehreraktivität", c: "TeacherActivity", w: 6, h: 4 },
        { i: "8", name: "Lernstrategien", c: "LearningStrategies", w: 6, h: 4 },
      ],

      // Current layout (reactive)
      currentLayout: [],
    };
  },

  provide() {
    return {
      editMode: () => this.editMode,
    };
  },

  async created() {
    await this.loadWidgetConfig();

    await this.$store.dispatch("dashboardSettings/getDashboardSettings");

    // Apply research condition filter
    if (this.research_condition === "control_group") {
      this.currentLayout = this.currentLayout.filter(
        (item) => item.c !== "Recommendations",
      );
    }
  },

  async mounted() {
    this.$store.commit("setResearchCondition");
    this.courseid = this.$store.state.courseid;
    this.context.courseId = this.$store.state.courseid;

    // Remove any existing style attribute from the grid container
    const gridEl = document.getElementById("widgetGrid");
    if (gridEl) {
      gridEl.removeAttribute("style");
    }

    // Wait for layout data to be ready, then initialize grid
    await this.$nextTick();
    // Additional tick to ensure v-for has rendered
    await this.$nextTick();
    this.initGrid();
    this.updateGridHeight();
  },

  updated() {
    this.recalculateHeight();
  },

  computed: {
    ...mapState({
      dashboardSettings: (state) => state.dashboardSettings.dashboardSettings,
      research_condition: (state) => state.research_condition,
      isModerator: (state) => state.isModerator,
      strings: "strings",
    }),

    /**
     * Get the current layout, filtering by enabled widgets
     */
    layout() {
      // Use currentLayout if available, otherwise defaultLayout
      const baseLayout =
        this.currentLayout.length > 0 ? this.currentLayout : this.defaultLayout;

      const enabledWidgetIds = this.widgetConfig.widgets
        .filter((w) => w.enabled)
        .map((w) => w.id.toLowerCase());

      // If no widgets are explicitly enabled (empty config), show all
      if (enabledWidgetIds.length === 0) {
        return baseLayout;
      }

      // Filter to only enabled widgets
      return baseLayout.filter((item) =>
        enabledWidgetIds.includes(item.c.toLowerCase()),
      );
    },

    /**
     * Layout with calculated x/y positions based on widget widths
     * Widgets are placed left-to-right, row by row (12 column grid)
     */
    layoutWithPositions() {
      const result = [];
      let currentX = 0;
      let currentY = 0;
      let rowMaxHeight = 0;

      for (const item of this.layout) {
        const w = item.w || 12;
        const h = item.h || 4;

        // If widget doesn't fit in current row, move to next row
        if (currentX + w > 12) {
          currentY += rowMaxHeight;
          currentX = 0;
          rowMaxHeight = 0;
        }

        result.push({
          ...item,
          x: currentX,
          y: currentY,
        });

        currentX += w;
        rowMaxHeight = Math.max(rowMaxHeight, h);
      }

      return result;
    },

    /**
     * Get widgets not currently in layout (for add dropdown)
     */
    filteredComponents() {
      const layoutIds = this.layout.map((item) => item.i);

      // Get enabled widget IDs from teacher configuration
      const enabledWidgetIds = this.widgetConfig.widgets
        .filter((w) => w.enabled)
        .map((w) => w.id.toLowerCase());

      // If no widgets are explicitly enabled, show all (for backward compatibility)
      const enabledComponents =
        enabledWidgetIds.length === 0
          ? this.allComponents
          : this.allComponents.filter((comp) =>
              enabledWidgetIds.includes(comp.c.toLowerCase()),
            );

      // Return only enabled widgets that are not already in layout
      return enabledComponents.filter((comp) => !layoutIds.includes(comp.i));
    },
  },

  methods: {
    ...mapGetters(["setResearchCondition"]),
    ...mapActions(["log"]),

    /**
     * Initializes the GridStack grid and sets up event listeners.
     * Also compacts the grid and sets the height based on actual rows.
     */
    initGrid() {
      this.grid = GridStack.init(
        {
          column: 12,
          cellHeight: 70,
          minRow: 0,
          animate: false,
          columnOpts: {
            breakpointForWindow: false,
            breakpoints: [{ w: 600, c: 1 }],
          },
          float: false,
          disableResize: !this.editMode,
          disableDrag: !this.editMode,
          resizable: {
            handles: "e, se, s, sw, w",
          },
        },
        "#widgetGrid",
      );

      // Sync position changes back to Vue data
      this.grid.on("change", (event, items) => {
        items.forEach((item) => {
          const widget = this.currentLayout.find(
            (w) => "widget-" + w.c === item.id,
          );
          if (widget) {
            widget.x = item.x;
            widget.y = item.y;
            widget.w = item.w;
            widget.h = item.h;
          }
        });
      });

      // Compact the grid to remove excess space
      this.grid.compact();

      // Calculate and set height after initialization
      this.$nextTick(() => {
        this.recalculateHeight();
      });

      this.initObserver();
    },

    /**
     * Calculates and sets the total grid height based on widget positions.
     * Ensures the grid container matches the effective height of all widgets.
     */
    updateGridHeight() {
      const cellHeight = 70;
      let sum_height = 0;

      for (const item of this.layoutWithPositions) {
        sum_height += item.h;
      }

      const gridEl = document.getElementById("widgetGrid");
      if (gridEl) {
        gridEl.style.height = sum_height * cellHeight + "px";
      }
    },

    /**
     * Recalculates the grid height based on actual child element positions.
     * Measures the bottom-most child and sets container height accordingly.
     */
    recalculateHeight() {
      const gridEl = document.getElementById("widgetGrid");
      if (!gridEl) {
        return;
      }

      const children = gridEl.children;
      if (children.length === 0) {
        gridEl.style.height = "0px";
        return;
      }

      // Find the maximum bottom position of all child elements
      let maxBottom = 0;
      for (let i = 0; i < children.length; i++) {
        const child = children[i];
        const bottom = child.offsetTop + child.offsetHeight;
        maxBottom = Math.max(maxBottom, bottom);
      }

      // Set grid height with small margin
      const finalHeight = maxBottom + 20;
      gridEl.style.height = finalHeight + "px";
    },

    /**
     * Loads the widget configuration from the backend via webservice.
     * Sets widgetConfig and handles errors.
     */
    async loadWidgetConfig() {
      try {
        Communication.setPluginName("format_serial3");
        const response = await Communication.webservice("get_widget_config", {
          courseid: this.$store.state.courseid,
        });

        if (response && response.success) {
          this.widgetConfig = response;
          console.log("Widget configuration loaded:", response);
        } else {
          console.warn("Widget config missing, using defaults");
          this.widgetConfig = { success: true, widgets: [], canManage: false };
        }
      } catch (error) {
        console.error("Failed to load widget configuration:", error);
        this.widgetConfig = { success: true, widgets: [], canManage: false };
      }
    },

    /**
     * Reloads the widget configuration by refreshing the page.
     * Useful for applying updated widget settings immediately.
     */
    reloadWidgetConfig() {
      window.location.reload();
    },

    /**
     * Initializes the IntersectionObserver for dashboard widgets.
     * Logs widget visibility events for analytics.
     */
    initObserver() {
      if (
        "IntersectionObserver" in window &&
        "IntersectionObserverEntry" in window &&
        "intersectionRatio" in window.IntersectionObserverEntry.prototype
      ) {
        const _this = this;
        const options = {
          root: null,
          rootMargin: "0px",
          threshold: [0.25, 0.5, 0.75, 1.0],
          trackVisibility: true,
          delay: 100,
        };

        const handleScrolling = function (entries) {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              _this.log({
                key: "view-dashboard-widget",
                value: {
                  widget: entry.target.id,
                  intersection: entry.intersectionRatio,
                },
              });
            }
          });
        };

        const observer = new IntersectionObserver(handleScrolling, options);
        const element = document.querySelector("#widgetGrid");
        if (element) {
          observer.observe(element);
        }

        const gridItems = document.querySelectorAll(
          "#widgetGrid .grid-stack-item",
        );
        gridItems.forEach((item) => {
          if (typeof item.id === "string" && item.id) {
            observer.observe(item);
          }
        });
      }
    },

    /**
     * Toggles edit mode for the dashboard, enabling or disabling grid move/resize.
     */
    toggleEditMode() {
      this.editMode = !this.editMode;
      if (this.grid) {
        this.grid.enableMove(this.editMode);
        this.grid.enableResize(this.editMode);
      }
    },

    /**
     * Adds a new widget to the dashboard layout from the dropdown selection.
     * Registers the widget with GridStack after rendering.
     */
    addItem(e) {
      const newItem = this.allComponents.find(
        (element) => element.i === e.target.value,
      );
      if (newItem) {
        const item = {
          ...newItem,
          x: 0,
          y: this.getMaxY() + 1,
        };
        this.currentLayout.push(item);

        // Wait for Vue to render, then register with GridStack
        this.$nextTick(() => {
          // Additional tick to ensure DOM is fully updated
          this.$nextTick(() => {
            const widgetEl = document.querySelector("#widget-" + item.c);
            if (widgetEl) {
              this.grid.makeWidget(widgetEl);
              // Recalculate height after adding widget
              this.$nextTick(() => {
                this.recalculateHeight();
              });
            } else {
              console.error(`Widget element not found: #widget-${item.c}`);
            }
          });
        });
      }
      e.target.selectedIndex = 0;
    },

    /**
     * Adds a new widget to the dashboard layout from the dropdown (alias for addItem).
     */
    addItemFromDropdown(e) {
      this.addItem(e);
    },

    /**
     * Returns the maximum y position (bottom row) among all widgets in the current layout.
     */
    getMaxY() {
      if (this.currentLayout.length === 0) return 0;
      return Math.max(
        ...this.currentLayout.map((item) => (item.y || 0) + (item.h || 1)),
      );
    },

    /**
     * Removes a widget from the dashboard layout and from GridStack.
     */
    removeItem(val) {
      if (!this.grid) {
        console.error("Grid not initialized");
        return;
      }

      // Find the widget in the layout to get its component name
      const layoutItem = this.layout.find((item) => item.i === val);
      if (!layoutItem) {
        console.error("Widget not found in layout with id:", val);
        return;
      }

      const elementId = "widget-" + layoutItem.c;

      // Query GridStack directly for the element
      const gridItems = this.grid.getGridItems();
      const element = gridItems.find((el) => el.id === elementId);

      if (element) {
        // Remove from GridStack
        this.grid.removeWidget(element, false);

        // Remove from our data structures
        // First, ensure currentLayout is populated
        if (this.currentLayout.length === 0) {
          this.currentLayout = [...this.defaultLayout];
        }

        // Remove from currentLayout
        const index = this.currentLayout.findIndex((item) => item.i === val);
        if (index > -1) {
          this.currentLayout.splice(index, 1);
        }

        // Recalculate height after removing widget
        this.$nextTick(() => {
          this.recalculateHeight();
        });
      } else {
        console.error(
          "Widget element not found in GridStack with id:",
          elementId,
        );
      }
    },

    /**
     * Saves the current dashboard layout to the Vuex store and backend.
     * Updates widget positions and disables edit mode.
     */
    saveDashboard() {
      if (!this.grid) {
        console.error("Grid not initialized");
        return;
      }

      // Get current positions from GridStack
      const gridData = this.grid.save();

      // Update currentLayout with new positions
      const updatedLayout = this.currentLayout.map((item) => {
        const gridItem = gridData.find(
          (g) => g.el?.id === "widget-" + item.c || g.id === "widget-" + item.c,
        );
        if (gridItem) {
          return {
            ...item,
            x: gridItem.x,
            y: gridItem.y,
            w: gridItem.w,
            h: gridItem.h,
          };
        }
        return item;
      });

      this.currentLayout = updatedLayout;
      const settings = JSON.stringify(updatedLayout);
      this.$store.dispatch("dashboardSettings/saveDashboardSettings", settings);
      this.toggleEditMode();

      // Recalculate height after saving
      this.$nextTick(() => {
        this.recalculateHeight();
      });
    },
  },

  watch: {
    /**
     * Watch for changes in dashboardSettings from the store and update currentLayout.
     * This ensures that when settings are loaded from the backend, they populate the layout.
     */
    dashboardSettings: {
      handler(newSettings) {
        if (newSettings && newSettings.length > 0) {
          this.currentLayout = newSettings;

          // Compact and recalculate after layout changes
          this.$nextTick(() => {
            if (this.grid) {
              this.grid.compact();
              this.$nextTick(() => {
                this.recalculateHeight();
              });
            }
          });
        }
      },
      immediate: true,
    },
  },
};
</script>

<style lang="scss">
body#page-course-view-serial3 #region-main-box:nth-child(1) {
  display: inline !important;
}

#app {
  position: relative;
  z-index: 1;
  display: block;
  clear: both;
}

.grid-stack {
  background: #eee;
}

.grid-stack > .grid-stack-item {
  background: #fff;
  border: 1px solid #ddd;
}

.grid-stack > .grid-stack-item > .grid-stack-item-content {
  overflow: auto;
  padding: 0px;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  position: absolute;
}

.remove {
  position: absolute;
  right: 8px;
  top: 0;
  cursor: pointer;
  color: #666666;
  z-index: 10;

  &:hover {
    color: black;
  }
}

.btn-edit {
  height: 35px;
}

select.form-control {
  appearance: menulist-button !important;
}
</style>
