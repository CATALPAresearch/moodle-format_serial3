<template>
  <div style="width: 100%; height: 100%">
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
        class="grid-stack-item vue-grid-item border p-3"
        v-for="(item, index) in layout"
        :id="'widget-' + item.c"
        :gs-w="item.w"
        :gs-h="item.h"
        :gs-min-h="item.h"
        :gs-no-resize="!editMode"
        gs-resize-handles="s"
      >
        <div class="grid-stack-item-content">
          <span
            v-if="editMode & !item.fixed"
            class="remove"
            :title="strings.dashboardRemoveItem"
            @click="removeItem(item.i)"
          >
            <i class="fa fa-close"></i>
          </span>
          <component :is="item.c" :ref="'widget-' + item.c"></component>
        </div>
      </div>
    </div>
    <welcome-video
      v-show="new Date().getTime() < new Date('2024.04.31').getTime()"
    ></welcome-video>
  </div>
</template>

<script>
// Core components
import MenuBar from "./components/MenuBar.vue";
import WelcomeVideo from "./components/WelcomeVideo.vue";
import SurveyPrompt from "./components/SurveyPrompt.vue";
import Communication from "./utils/communication";

// Widget imports - now from organized widget folders
import AppDeadlines from "./widgets/Deadlines";
import IndicatorDisplay from "./widgets/IndicatorDisplay";
import ProgressChartAdaptive from "./widgets/ProgressChartAdaptive";
import Recommendations from "./widgets/Recommendations";
import TaskList from "./widgets/TaskList";
import LearningStrategies from "./widgets/LearningStrategies";
import CourseOverview from "./widgets/CourseOverview";
import TeacherActivity from "./widgets/TeacherActivity";
//import QuizStatistics from "./widgets/QuizStatistics";

import "gridstack/dist/gridstack.min.css";
import { GridStack } from "gridstack";
import { mapState, mapGetters, mapActions } from "vuex";

export default {
  components: {
    AppDeadlines: AppDeadlines.component,
    IndicatorDisplay: IndicatorDisplay.component,
    MenuBar,
    WelcomeVideo,
    SurveyPrompt,
    ProgressChartAdaptive: ProgressChartAdaptive.component,
    Recommendations: Recommendations.component,
    TaskList: TaskList.component,
    CourseOverview: CourseOverview.component,
    LearningStrategies: LearningStrategies.component,
    TeacherActivity: TeacherActivity.component,
    //QuizStatistics: QuizStatistics.component
  },

  data() {
    return {
      courseid: -1,
      context: {},
      logger: null,

      grid: undefined,
      count: 0,
      info: "",
      timerId: undefined,

      editMode: false,
      widgetConfig: null, // Stores enabled widgets and their settings

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
          i: "9",
          name: "Feedback",
          c: "Recommendations",
        },
        {
          x: 6,
          y: 10,
          w: 3,
          h: 4,
          i: "3",
          name: "Aufgabenliste",
          c: "TaskList",
        },
        {
          x: 9,
          y: 10,
          w: 3,
          h: 4,
          i: "4",
          name: "Termine",
          c: "AppDeadlines",
        },
        {
          x: 0,
          y: 14,
          w: 12,
          h: 5,
          i: "12",
          name: "Kursübersicht",
          c: "CourseOverview",
        },
        {
          x: 0,
          y: 19,
          w: 12,
          h: 5,
          i: "10",
          name: "Aktivitäten der Lehrenden",
          c: "TeacherActivity",
        },
      ],

      allComponents: [
        {
          x: 0,
          y: 0,
          w: 6,
          h: 5,
          i: "10",
          name: "Lehrenden Dashboard",
          c: "TeacherActivity",
        },
        {
          x: 0,
          y: 0,
          w: 8,
          h: 12,
          i: "1",
          name: "Überblick über den Kurs und die Kurseinheiten",
          c: "ProgressChartAdaptive",
        },
        {
          x: 8,
          y: 0,
          w: 6,
          h: 12,
          i: "2",
          name: "Lernziele",
          c: "IndicatorDisplay",
        },
        {
          x: 10,
          y: 12,
          w: 4,
          h: 10,
          i: "3",
          name: "Aufgaben",
          c: "TaskList",
        },
        {
          x: 6,
          y: 12,
          w: 4,
          h: 10,
          i: "4",
          name: "Termine",
          c: "AppDeadlines",
        },
        {
          x: 0,
          y: 12,
          w: 6,
          h: 10,
          i: "9",
          name: "Feedback und Lernempfehlungen",
          c: "Recommendations",
        },

        /*{
                    "x": 0,
                    "y": 0,
                    "w": 6,
                    "h": 12,
                    "i": "7",
                    "name": 'Ergebnisse',
                    c: 'QuizStatistics',
                    resizable: true
                },*/

        {
          x: 0,
          y: 0,
          w: 12,
          h: 4,
          i: "11",
          name: "Lernstrategien",
          c: "LearningStrategies",
        },
      ],
    };
  },

  async created() {
    await this.loadWidgetConfig();

    // Filter components based on enabled widgets
    if (this.widgetConfig) {
      const enabledWidgetIds = this.widgetConfig.widgets
        .filter((w) => w.enabled)
        .map((w) => w.id);
      this.allComponents = this.allComponents.filter((component) =>
        this.isWidgetEnabled(component.c, enabledWidgetIds),
      );
      this.defaultLayout = this.defaultLayout.filter((component) =>
        this.isWidgetEnabled(component.c, enabledWidgetIds),
      );
    }

    this.loadDashboard();
  },

  mounted: function () {
    this.$store.commit("setResearchCondition");

    this.courseid = this.$store.state.courseid;

    this.context.courseId = this.$store.state.courseid; // TODO

    this.grid = GridStack.init({
      column: 12,
      cellHeight: 80,
      minRow: 1,
      animate: false, // show immediate (animate: true is nice for user dragging though)
      columnOpts: {
        breakpointForWindow: false, // test window vs grid size
        //breakpoints: [{w:300, c:6},{w:400, c:8},{w:600, c:12},{w:1100, c:12}]
        //breakpoints: [{w:220, c:1},{w:600, c:6}, {w:800, c:12}]
        breakpoints: [{ w: 600, c: 1 }],
      },
      float: true,
      disableResize: true,
      disableMove: true,
      resizable: {
        handles: "e, se, s, sw, w",
      },
    });

    // Explicitly disable dragging and resizing after grid initialization
    this.$nextTick(function () {
      if (this.grid) {
        this.grid.enableMove(false);
        this.grid.enableResize(false);
      }
      this.initObserver();
    });
  },

  computed: {
    filteredComponents() {
      return this.allComponents.filter(
        ({ i: id1 }) => !this.layout.some(({ i: id2 }) => id2 === id1),
      );
    },

    layout() {
      let r =
        this.dashboardSettings && this.dashboardSettings.length > 0
          ? this.dashboardSettings
          : this.defaultLayout;

      // Filter by widget configuration
      if (this.widgetConfig) {
        const enabledWidgetIds = this.widgetConfig.widgets
          .filter((w) => w.enabled)
          .map((w) => w.id);
        r = r.filter((component) =>
          this.isWidgetEnabled(component.c, enabledWidgetIds),
        );
      }

      return r;
    },

    ...mapState({
      dashboardSettings: (state) => state.dashboardSettings.dashboardSettings,
      research_condition: (state) => state.research_condition,
      isModerator: (state) => state.isModerator,
      strings: "strings",
    }),
  },

  methods: {
    ...mapGetters(["setResearchCondition"]),
    ...mapActions(["log"]),

    async loadWidgetConfig() {
      try {
        Communication.setPluginName("format_serial3");
        const response = await Communication.webservice("get_widget_config", {
          courseid: this.$store.state.courseid,
        });

        if (response.success) {
          this.widgetConfig = response;
          console.log("Widget configuration loaded:", response);
        }
      } catch (error) {
        console.error("Failed to load widget configuration:", error);
        // Continue without widget filtering if config fails to load
      }
    },

    isWidgetEnabled(componentName, enabledWidgetIds) {
      // Map component names to widget IDs (must match backend widget IDs in lowercase)
      const widgetMap = {
        TaskList: "tasklist",
        AppDeadlines: "deadlines",
        ProgressChartAdaptive: "progresschartadaptive",
        Recommendations: "recommendations",
        IndicatorDisplay: "indicatordisplay",
        LearningStrategies: "learningstrategies",
        CourseOverview: "courseoverview",
        TeacherActivity: "teacheractivity",
        QuizStatistics: "quizstatistics",
      };

      const widgetId = widgetMap[componentName];
      // If widget not in map or no enabled list, show it (fail-safe)
      return (
        !widgetId || !enabledWidgetIds || enabledWidgetIds.includes(widgetId)
      );
    },

    async reloadWidgetConfig() {
      // Reload the page to apply new widget configuration
      // This is simpler than trying to dynamically update all arrays
      window.location.reload();
    },

    refreshAllWidgets() {
      // Iterate through all widget refs and call their loadData methods
      Object.keys(this.$refs).forEach((refKey) => {
        if (refKey.startsWith("widget-")) {
          const widgetRefs = this.$refs[refKey];
          // Handle both single and array refs
          const widgets = Array.isArray(widgetRefs) ? widgetRefs : [widgetRefs];

          widgets.forEach((widget) => {
            if (widget && typeof widget.loadData === "function") {
              widget.loadData();
            }
          });
        }
      });
    },

    initObserver() {
      if (
        "IntersectionObserver" in window &&
        "IntersectionObserverEntry" in window &&
        "intersectionRatio" in window.IntersectionObserverEntry.prototype
      ) {
        let _this = this;
        let options = {
          root: null,
          rootMargin: "0px",
          threshold: [0.25, 0.5, 0.75, 1.0],
          trackVisibility: true,
          delay: 100,
        };

        let handleScrolling = function (entries) {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              //console.log('Scroll Output: ',entry.target.id, entry.intersectionRatio);
              _this.log({
                key: "view-dashboard-widget",
                value: {
                  widget: entry.target.id,
                  intersection: entry.intersectionRatio,
                },
              });
            }
            //   entry.boundingClientRect
            //   entry.intersectionRatio
            //   entry.intersectionRect
            //   entry.isIntersecting
            //   entry.rootBounds
            //   entry.target
            //   entry.time
          });
        };

        let observer = new IntersectionObserver(handleScrolling, options);
        var element = document.querySelector("#widgetGrid");
        if (typeof element != "undefined" && element != null) {
          observer.observe(element);
        }

        const gridItems = document.querySelectorAll(
          "#widgetGrid .vue-grid-item",
        );
        gridItems.forEach(function (item) {
          if (typeof item.id === "string" && item.id) {
            observer.observe(item);
          }
        });
      }
    },
    addItem(e) {
      const newItem = this.allComponents.find(
        (element) => element.i === e.target.value,
      );
      this.layout.push(newItem);
      this.$el.querySelector("#addDashboardItems").selectedIndex = 0;
    },

    removeItem(val) {
      const index = this.layout.map((item) => item.i).indexOf(val);
      this.layout.splice(index, 1);
    },

    toggleEditMode() {
      this.editMode = !this.editMode;
      this.draggable = this.resizable = this.editMode;

      // Enable or disable dragging and resizing in GridStack
      if (this.grid) {
        if (this.editMode) {
          this.grid.enableMove(true);
          this.grid.enableResize(true);
        } else {
          this.grid.enableMove(false);
          this.grid.enableResize(false);
        }
      }
    },

    loadDashboard: function () {
      this.$store.dispatch("dashboardSettings/getDashboardSettings");
    },

    saveDashboard() {
      const settings = JSON.stringify(this.layout);
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
}

.vue-grid-item:not(.vue-grid-placeholder) {
  background: #fff;
  border: 1px solid black;
}

.vue-grid-item .resizing {
  opacity: 0.9;
}

.vue-grid-item .no-drag {
  height: 100%;
  width: 100%;
}

.grid-stack-item-content {
  overflow: auto !important;
  inset: 0px !important;
}

.grid-stack > .grid-stack-item > .grid-stack-item-content {
  overflow: auto;
}

.vue-grid-item .minMax {
  font-size: 12px;
}

.vue-grid-item .add {
  cursor: pointer;
}

.vue-draggable-handle {
  position: absolute;
  width: 20px;
  height: 20px;
  top: 0;
  left: 0;
  background-position: bottom right;
  padding: 0 8px 8px 0;
  background-repeat: no-repeat;
  background-origin: content-box;
  box-sizing: border-box;
  cursor: pointer;
}

.remove {
  position: absolute;
  right: 8px;
  top: 0;
  cursor: pointer;
  color: #666666;

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
