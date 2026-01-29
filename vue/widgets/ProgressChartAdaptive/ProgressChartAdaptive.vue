<template>
  <div class="position-relative h-100 d-flex flex-column">
    <widget-heading
      :info-content="info"
      icon="fa-hourglass-o"
      title="Überblick über den Kurs und die Kurseinheiten"
    ></widget-heading>

    <div class="subject-progress px-1">
      <div
        :class="currentSection === -1 ? 'section-selection--current' : ''"
        class="section-selection mr-2"
        @click="setCurrentSection(-1)"
      >
        <p class="my-1 pl-1">Alle Kurseinheiten</p>
        <div class="progress mb-2">
          <div
            :aria-valuenow="calculateProgress"
            :style="{ width: calculateProgress + '%' }"
            aria-valuemax="100"
            aria-valuemin="0"
            class="progress-bar progress-bar-blue"
            role="progressbar"
          >
            {{ calculateProgress }}%
          </div>
        </div>
      </div>

      <div class="w-100 mb-4">
        <div
          v-for="(section, index) in getSections"
          :key="index"
          :style="{ width: calculateWidth(getSections.length) + '%' }"
          class="d-inline-block"
          @click="setCurrentSection(index)"
        >
          <div
            :class="
              currentSection === index ? 'section-selection--current' : ''
            "
            class="section-selection mr-2"
          >
            <p
              :title="section[0]?.sectionname || ''"
              class="section-names mb-1 small pl-1"
            >
              {{ section[0]?.sectionname || "" }}
            </p>
            <div class="progress">
              <div
                :aria-valuenow="calculateSectionProgress(section)"
                :style="{ width: calculateSectionProgress(section) + '%' }"
                aria-valuemax="100"
                aria-valuemin="0"
                class="progress-bar progress-bar-blue"
                role="progressbar"
              >
                {{ calculateSectionProgress(section) }}%
              </div>
            </div>
          </div>
        </div>
      </div>
      <div
        v-if="research_condition != 'control_group'"
        class="course-recommendation py-2"
      >
        <div
          v-for="(recommendation, index) in getCourseRecommendations"
          :key="index"
        >
          <RecommendationItem
            v-if="
              index < 1 &&
              currentSection == -1 &&
              recommendation.type == 'scope_course'
            "
            :recommendation="recommendation"
            :courseid="$store.getters.getCourseid"
            :timeAgo="timeAgo"
            :mode="'minimal'"
          ></RecommendationItem>
        </div>
      </div>
      <div
        v-for="(type, typeIndex) in activityTypes"
        :key="typeIndex"
        class="row"
      >
        <span
          v-if="
            getActivities[type]?.[0] &&
            isIncludedActivity(getActivities[type][0].type)
          "
          class="col-3"
          >{{ mapActivityNames[getActivities[type][0].modulename] }}</span
        >
        <div class="col-9">
          <template
            v-for="activity in currentActivities[type]"
            :key="activity.id"
          >
            <span
              v-if="activity && isIncludedActivity(activity.type)"
              class="position-relative"
            >
              <button
                :ref="'activity-' + activity.id"
                class="subject-progress__popover"
                type="button"
                :title="
                  activity.completion !== 0
                    ? activity.name + ' (bereits bearbeitet)'
                    : activity.name
                "
                @click="togglePopover($event, activity)"
              >
                <span
                  :class="{
                    'rect--grey': activity.rating === 0,
                    'rect--weak': activity.rating === 1,
                    'rect--ok': activity.rating === 2,
                    'rect--strong': activity.rating === 3,
                    'activity-completed': activity.completion !== 0,
                  }"
                  :title="activity.name"
                  class="completion-rect"
                ></span>
              </button>
            </span>
          </template>
        </div>
      </div>
      <div class="legend d-flex justify-content-start mt-3">
        <div class="d-flex align-items-center mr-3">
          <span class="completion-rect rect-sm rect--grey mr-1"></span
          ><span class="">Nicht abgeschlossen</span>
        </div>
        <div class="d-flex align-items-center mr-3">
          <span class="completion-rect rect-sm rect--weak mr-1"></span
          ><span class="">Ungenügend verstanden</span>
        </div>
        <div class="d-flex align-items-center mr-3">
          <span class="completion-rect rect-sm rect--ok mr-1"></span
          ><span class="">Größtenteils verstanden</span>
        </div>
        <div class="d-flex align-items-center">
          <span class="completion-rect rect-sm rect--strong mr-1"></span
          ><span class="">Alles verstanden</span>
        </div>
      </div>
    </div>

    <!-- Vue Popover -->
    <div
      v-if="activePopover"
      ref="popoverContainer"
      class="vue-popover"
      :style="popoverStyle"
      @click.stop
    >
      <div class="popover-arrow"></div>
      <PopoverContent
        :activity="activePopover"
        :courseid="$store.getters.getCourseid"
        @understanding-updated="handleUnderstandingUpdated"
        @add-to-task-list="handleAddToTaskList"
        @logg="log"
      ></PopoverContent>
    </div>
  </div>
</template>

<script>
import Communication from "../../utils/communication";
import mockDataHelper from "../../utils/mockDataHelper";
import WidgetHeading from "../../components/WidgetHeading.vue";
import RecommendationItem from "../../components/RecommendationItem.vue";
import { mapActions, mapGetters, mapState } from "vuex";
import { groupBy } from "../../utils/util";
import PopoverContent from "../../components/PopoverContent.vue";
//import { treemapSquarify } from '../../../lib/src/d3.v4';

export default {
  name: "WidgetProgressChartAdaptive",

  components: { WidgetHeading, PopoverContent, RecommendationItem },

  data: function () {
    return {
      total: 0,
      activePopover: null,
      popoverStyle: {},
      info:
        "Das Widget bietet Ihnen eine Übersicht über alle Kursaktivitäten. Für jede Aktivität können Sie Ihr Verständnis bewerten, den Bearbeitungsstand sehen oder es zur Aufgabenliste hinzufügen. Über den Aktivitäten wird Ihnen eine Fortschrittsanzeige angezeigt, die anzeigt, wie viele Aktivitäten Sie insgesamt und für jede Kurseinheit separat bereits abgeschlossen haben. Diese dienen Ihnen auch als Filter, um die dir nur die Aktivitäten für die jeweilige Kurseinheit anzuzeigen.\n" +
        "\n" +
        "Dieses Widget hilft Ihnen Ihre Lernaktivitäten im Blick zu behalten und Ihre Fortschritte zu verfolgen. Durch die Bewertung Ihres Verständnisses können Sie schnell erkennen, welche Aktivitäten noch unklar sind. Das Hinzufügen von Aktivitäten zur Aufgabenliste ermöglicht es Ihnen, Ihre Aufgaben zu organisieren und Prioritäten zu setzen.",
      sectionnames: [],
      stats: [],
      currentSection: -1,
      mapActivityNames: {
        longpage: "Kurstext",
        Longpage: "Kurstext",
        Assignment: "Einsendeaufgaben",
        Aufgabe: "Einsendeaufgaben",
        assign: "Einsendeaufgaben",
        hypervideo: "Video",
        Safran: "Self-Assessments",
        safran: "Self-Assessments",
        quiz: "Selbsttests",
        Test: "Selbsttests",
      },
    };
  },

  watch: {
    currentSection: {
      immediate: true,
      handler(val) {
        if (val === -1) {
          this.$store.commit(
            "overview/setCurrentActivities",
            this.getActivities,
          );
        } else {
          this.$store.commit(
            "overview/setCurrentActivities",
            groupBy(this.getSections[this.currentSection], "type"),
          );
        }
      },
    },
  },

  mounted: async function () {
    // Add click outside listener
    document.addEventListener("click", this.handleClickOutside);
    await this.loadData();
    //await this.loadLearnerModel()
  },

  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
  },

  computed: {
    calculateProgress() {
      if (!this.getSections || this.getSections.length === 0) {
        return 0;
      }
      const x = this.getSections.map(
        (a) => a.filter(({ rating }) => rating !== 0).length,
      );
      const sum = x.reduce((total, current) => {
        return total + current;
      }, 0);
      const total = this.getTotalActivites();
      if (total === 0) {
        return 0;
      }
      const result = Math.floor((sum / total) * 100);
      return result;
    },

    currentActivities() {
      if (this.currentSection === -1) {
        return this.getActivities;
      } else {
        const section = this.getSections[this.currentSection];
        return groupBy(section, "type");
      }
    },
    //
    ...mapState(
      //'overview', ['courseData', 'activityTypes'],
      {
        activityTypes: (state) => state.overview.activityTypes,
        courseData: (state) => state.overview.courseData,
        research_condition: (state) => state.research_condition,
      },
    ),
    ...mapGetters("overview", [
      "getSections",
      "getActivities",
      "getCurrentActivities",
    ]),
    ...mapGetters("recommendations", [
      "getRecommendations",
      "getCourseRecommendations",
      "getCourseUnitRecommendations",
      "getActivityTypeRecommendations",
      "getActivityRecommendations",
    ]),
  },

  methods: {
    ...mapActions("taskList", ["addItem"]),
    ...mapActions(["log"]),

    togglePopover(event, activity) {
      event.stopPropagation();

      this.log({
        key: "activity-popover-show",
        value: { id: activity.id, name: activity.name },
      });

      if (this.activePopover?.id === activity.id) {
        this.activePopover = null;
        return;
      }

      this.activePopover = activity;

      this.$nextTick(() => {
        this.positionPopover(event.currentTarget);
      });
    },

    positionPopover(buttonElement) {
      if (!buttonElement || !this.$refs.popoverContainer) return;

      const buttonRect = buttonElement.getBoundingClientRect();

      // Position below the button using fixed positioning (relative to viewport)
      const top = buttonRect.bottom + 8;
      const left = buttonRect.left + buttonRect.width / 2;

      this.popoverStyle = {
        position: "fixed",
        top: `${top}px`,
        left: `${left}px`,
        transform: "translateX(-50%)",
        zIndex: 1060,
      };
    },

    handleClickOutside(event) {
      if (!this.activePopover) return;

      const popoverContainer = this.$refs.popoverContainer;
      if (popoverContainer && !popoverContainer.contains(event.target)) {
        this.activePopover = null;
      }
    },

    handleUnderstandingUpdated(understanding, activityId) {
      if (this.courseData.hasOwnProperty(activityId)) {
        this.courseData[activityId].rating = Number(understanding);
        this.log({
          key: "activity-popover-rate-understanding",
          value: {
            id: this.courseData[activityId].id,
            name: this.courseData[activityId].name,
            understanding: understanding,
          },
        });

        if (understanding === 0) {
          this.courseData[activityId].completion = 0;
        } else {
          this.courseData[activityId].completion = 1;
        }
      }
      // Close popover after rating
      this.activePopover = null;
    },

    handleAddToTaskList(task) {
      this.log({
        key: "activity-popover-addToTaskList",
        value: { id: task.id, name: task.name },
      });
      delete task.id;
      delete task.name;
      this.addItem(task);
      // Close popover after adding to task list
      this.activePopover = null;
    },

    calculateSectionProgress(section) {
      if (!section || section.length === 0) {
        return 0;
      }
      const sum = section.filter(({ rating }) => rating !== 0).length;
      const total = section.length;
      return Math.floor((sum / total) * 100);
    },

    calculateWidth(items) {
      return 100 / items;
    },

    getTotalActivites() {
      if (!this.getSections || this.getSections.length === 0) {
        return 0;
      }
      const y = this.getSections.map((a) => a.length);
      return y.reduce((total, current) => {
        return total + current;
      }, 0);
    },

    isIncludedActivity(activity) {
      const includedActivities = [
        "hypervideo",
        "assign",
        "safran",
        "longpage",
        "questionnaire",
      ];
      if (includedActivities.indexOf(activity) > -1) {
        return true;
      }
      return false;
    },

    setCurrentSection(section) {
      this.currentSection = section;
      this.$store.commit("overview/setCurrentSection", section);
      this.log({ key: "progress-chart-select-section", value: section });
    },

    /**
     * TODO
     */
    loadLearnerModel() {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
          var lm = JSON.parse(this.responseText);
          // TODO convert LM to internal data structure
        }
      };
      // FIXME: pass the moodle path from php to the client
      var wwwroot =
        window.location.protocol +
        "//" +
        window.location.host +
        "/" +
        window.location.pathname;
      wwwroot = "http://localhost/moodle311";
      // FIXME: add the current semester as a parameter of the path instead of "SS23"
      var path =
        wwwroot +
        "/local/ari/lm/learner_model.php?format=json&period=SS23&course_id=" +
        parseInt(this.$store.getters.getCourseid, 10) +
        "&user_id=" +
        parseInt(this.$store.getters.getUserid, 10);
      xmlhttp.open("GET", path, true);
      xmlhttp.send();
    },

    loadData: async function () {
      // Check if mock data is enabled
      if (mockDataHelper.isMockDataEnabled(this.$store)) {
        const result = await mockDataHelper.loadWidgetData(
          this.$store,
          "ProgressChartAdaptive",
          null,
        );

        if (result.success && result.isMockData && result.data) {
          const mockData = result.data;
          console.log("[Mock Data] Using mock data for ProgressChartAdaptive");

          if (mockData.courseData) {
            this.$store.commit("overview/setCourseData", mockData.courseData);
            this.$store.commit(
              "overview/setCurrentActivities",
              this.getActivities,
            );
            this.$store.commit(
              "overview/setActivityTypes",
              Object.keys(this.getActivities),
            );
            this.total = this.getTotalActivites();
          }

          if (mockData.proficiency !== undefined) {
            this.$store.commit(
              "learnermodel/setProficiency",
              mockData.proficiency,
            );
          }
          if (mockData.progressUnderstanding !== undefined) {
            this.$store.commit(
              "learnermodel/setProgressUnderstanding",
              mockData.progressUnderstanding,
            );
          }
          return;
        }
      }

      // Load real data from database
      const response = await Communication.webservice("overview", {
        courseid: parseInt(this.$store.getters.getCourseid, 10),
      });
      if (response.success) {
        response.data = JSON.parse(response.data);
        const completions = JSON.parse(response.data.completions);

        console.log("[ProgressChartAdaptive] Loaded course data:", {
          totalActivities: Object.keys(completions).length,
          sections: Object.values(completions).reduce((acc, item) => {
            if (!acc.includes(item.section)) acc.push(item.section);
            return acc;
          }, []).length,
        });

        this.$store.commit("overview/setCourseData", completions);
        this.$store.commit("overview/setCurrentActivities", this.getActivities);
        this.$store.commit(
          "overview/setActivityTypes",
          Object.keys(this.getActivities),
        );
        this.total = this.getTotalActivites();
      } else {
        if (response.data) {
          console.error(
            "[ProgressChartAdaptive] Faulty response from webservice /overview/:",
            response.data,
          );
        } else {
          console.error(
            "[ProgressChartAdaptive] No connection to webservice /overview/",
          );
        }
      }

      const completionData = this.$store.state.learnermodel.userUnderstanding;
      let appliedCount = 0;
      for (let key in completionData) {
        let activityid = completionData[key]["activityid"];
        if (this.courseData.hasOwnProperty(activityid)) {
          this.courseData[activityid]["completion"] = Number(
            completionData[key]["completed"],
          );
          this.courseData[activityid]["rating"] = Number(
            completionData[key]["rating"],
          );
          appliedCount++;
        }
      }
      console.log(
        `[ProgressChartAdaptive] Applied user understanding data to ${appliedCount} activities`,
      );
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../scss/variables.scss";
@import "../../scss/scrollbar.scss";

.course-recommendation {
  padding: 10px 0px;
  color: $blue-dark;
  font-size: 1.2em;
}

.subject-progress {
  overflow-y: auto;
  overflow-x: hidden;

  &__popover {
    border: none;
    padding: 0;
    background: none;
  }
}

.progress-bar-blue {
  background-color: $blue-dark;
  opacity: 0.8;
}

.completion-rect {
  stroke-width: 3px;
  stroke: white;
  width: 20px;
  height: 18px;
  display: inline-block;
  opacity: 0.8;
  margin-right: 1px;
}

.rect-sm {
  width: 12px;
  height: 12px;
}

.rect--grey {
  background-color: $light-grey;
}

.rect--ok {
  background-color: $blue-middle;
}

.rect--strong {
  background-color: $blue-dark;
}

.rect--weak {
  background-color: $blue-weak;
}

.completion-rect:hover {
  stroke-width: 3px;
  stroke: white;
  opacity: 1;
}

.progressbar {
  width: 100%;
  height: 40px;
}

.section-names {
  white-space: nowrap;
  overflow: hidden !important;
  text-overflow: ellipsis;
}

.section-selection {
  cursor: pointer;
  margin: 2px;

  &:hover {
    text-decoration: underline;
    outline: 2px solid $blue-default;
    outline-offset: 0px;
  }

  &--current {
    text-decoration: underline;
    outline: 2px solid $blue-default;
    outline-offset: 0px;
  }
}

.button {
  border: none;
  background: none;
  padding: 0;
}

.container {
  margin-top: 10px;
}

.my-popover-content {
  display: none;
}

.activity-completed {
  border-bottom: solid 2px #555;
}

/* Vue Popover Styles */
.vue-popover {
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 0.3rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  padding: 0.5rem 0.75rem;
  min-width: 250px;
  max-width: 350px;
}

.popover-arrow {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-bottom: 8px solid white;
}

.popover-arrow::before {
  content: "";
  position: absolute;
  top: -1px;
  left: -8px;
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-bottom: 8px solid rgba(0, 0, 0, 0.2);
}
</style>
