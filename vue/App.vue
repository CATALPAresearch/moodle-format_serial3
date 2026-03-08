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
        </div>
        <menu-bar
          @editmode="toggleEditMode"
          @widgetsUpdated="reloadWidgetConfig"
          @refreshWidgets="refreshAllWidgets"
        ></menu-bar>
      </div>
    </div>
    <div id="widgetGrid" class="grid-stack vue-grid-layout">
      <div
        class="grid-stack-item border"
        v-for="item in layout"
        :key="item.i"
        :id="'widget-' + item.c"
        :gs-w="item.w"
        :gs-h="item.h"
        :gs-x="item.x"
        :gs-y="item.y"
      >
        <div class="grid-stack-item-content" style="overflow: auto">
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

      grid: undefined,
      editMode: false,
      widgetConfig: { success: false, widgets: [], canManage: false },

      // Default layout for widgets
      defaultLayout: [
        {
          x: 0,
          y: 0,
          w: 12,
          h: 5,
          i: "1",
          name: "Adaptiver Überblick",
          c: "ProgressChartAdaptive",
        },
        {
          x: 0,
          y: 5,
          w: 12,
          h: 5,
          i: "2",
          name: "Lernziele",
          c: "IndicatorDisplay",
        },
        {
          x: 0,
          y: 10,
          w: 6,
          h: 4,
          i: "3",
          name: "Feedback",
          c: "Recommendations",
        },
        {
          x: 6,
          y: 10,
          w: 3,
          h: 4,
          i: "4",
          name: "Aufgabenliste",
          c: "TaskList",
        },
        {
          x: 9,
          y: 10,
          w: 3,
          h: 4,
          i: "5",
          name: "Termine",
          c: "Deadlines",
        },
        {
          x: 0,
          y: 14,
          w: 12,
          h: 3,
          i: "6",
          name: "Kursübersicht",
          c: "CourseOverview",
        },
        {
          x: 0,
          y: 17,
          w: 12,
          h: 4,
          i: "7",
          name: "Lehreraktivität",
          c: "TeacherActivity",
        },
        {
          x: 6,
          y: 17,
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

  async created() {
    await this.loadWidgetConfig();
    await this.loadDashboard();
    this.initializeLayout();
  },

  async mounted() {
    this.$store.commit("setResearchCondition");
    this.courseid = this.$store.state.courseid;
    this.context.courseId = this.$store.state.courseid;

    // Wait for layout data to be ready, then initialize grid
    await this.$nextTick();
    // Additional tick to ensure v-for has rendered
    await this.$nextTick();
    this.initGrid();
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
     * Get widgets not currently in layout (for add dropdown)
     */
    filteredComponents() {
      const layoutIds = this.layout.map((item) => item.i);
      return this.allComponents.filter((comp) => !layoutIds.includes(comp.i));
    },
  },

  watch: {
    // Re-initialize grid when layout changes
    layout: {
      handler() {
        this.$nextTick(() => {
          if (this.grid && this.layout.length > 0) {
            this.grid.batchUpdate();
            const items = document.querySelectorAll(
              "#widgetGrid > .grid-stack-item",
            );
            items.forEach((el) => {
              if (!el.gridstackNode) {
                this.grid.makeWidget(el);
              }
            });
            this.grid.batchUpdate(false);
          }
        });
      },
      deep: true,
    },
  },

  methods: {
    ...mapGetters(["setResearchCondition"]),
    ...mapActions(["log"]),

    initGrid() {
      this.grid = GridStack.init(
        {
          column: 12,
          cellHeight: 80,
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

      this.initObserver();
    },

    initializeLayout() {
      // Always use defaultLayout - ignore saved settings that may have corrupted y-values
      // TODO: Re-enable saved layouts once the grid positioning is stable
      this.currentLayout = [...this.defaultLayout];

      // Apply research condition filter
      if (this.research_condition === "control_group") {
        this.currentLayout = this.currentLayout.filter(
          (item) => item.c !== "Recommendations",
        );
      }
    },

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

    async loadDashboard() {
      await this.$store.dispatch("dashboardSettings/getDashboardSettings");
    },

    reloadWidgetConfig() {
      window.location.reload();
    },

    refreshAllWidgets() {
      // Refresh all widget data - widgets should implement loadData method
      console.log("Refreshing all widgets...");
    },

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

    toggleEditMode() {
      this.editMode = !this.editMode;

      if (this.grid) {
        this.grid.enableMove(this.editMode);
        this.grid.enableResize(this.editMode);
      }
    },

    addItem(e) {
      const newItem = this.allComponents.find(
        (element) => element.i === e.target.value,
      );
      if (newItem) {
        this.currentLayout.push({
          ...newItem,
          x: 0,
          y: this.getMaxY() + 1,
        });
      }
      e.target.selectedIndex = 0;
    },

    addItemFromDropdown(e) {
      this.addItem(e);
    },

    getMaxY() {
      if (this.currentLayout.length === 0) return 0;
      return Math.max(
        ...this.currentLayout.map((item) => (item.y || 0) + (item.h || 1)),
      );
    },

    removeItem(val) {
      const index = this.currentLayout.findIndex((item) => item.i === val);
      if (index > -1) {
        this.currentLayout.splice(index, 1);
      }
    },

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
    },
  },
};
</script>

<style lang="scss">
body#page-course-view-serial3 #region-main-box:nth-child(1) {
  display: inline !important;
}

.vue-grid-layout {
  background: #eee;
  position: relative;
  min-height: auto !important;
}

.grid-stack {
  background: #eee;
  min-height: auto !important;
}

.grid-stack > .grid-stack-item {
  background: #fff;
  border: 1px solid #ddd;
}

.grid-stack > .grid-stack-item > .grid-stack-item-content {
  overflow: auto;
  padding: 10px;
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
