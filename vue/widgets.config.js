/**
 * Widget Registry and Configuration
 *
 * This file defines all available widgets, their dependencies, and metadata.
 * Widgets are organized as self-contained modules with optional routers and stores.
 */

export const widgetRegistry = {
  ProgressChartAdaptive: {
    name: "Adaptiver Überblick",
    component: () => import("./widgets/ProgressChartAdaptive"),
    dependencies: {
      stores: ["overview", "recommendations", "taskList"],
      sharedState: ["courseid", "userid", "research_condition"],
      description:
        "Uses overview store for activities, recommendations store for feedback, taskList for adding items",
    },
    defaultLayout: { x: 0, y: 0, w: 12, h: 5 },
  },

  IndicatorDisplay: {
    name: "Lernziele",
    component: () => import("./widgets/IndicatorDisplay"),
    dependencies: {
      stores: ["learnermodel"],
      sharedState: ["courseid", "userid"],
      description:
        "Uses root store for learner goal, learnermodel for understanding data",
    },
    defaultLayout: { x: 0, y: 5, w: 12, h: 5 },
  },

  Recommendations: {
    name: "Feedback",
    component: () => import("./widgets/Recommendations"),
    store: () => import("./widgets/Recommendations/store"),
    dependencies: {
      stores: ["recommendations"],
      sharedState: ["courseid", "userid"],
      description: "Self-contained widget with own store for recommendations",
    },
    defaultLayout: { x: 0, y: 10, w: 6, h: 4 },
  },

  TaskList: {
    name: "Aufgabenliste",
    component: () => import("./widgets/TaskList"),
    store: () => import("./widgets/TaskList/store"),
    dependencies: {
      stores: ["taskList"],
      sharedState: ["courseid", "userid"],
      crossWidgetUsage: ["ProgressChartAdaptive calls taskList/addItem"],
      description:
        "Self-contained widget, but actions are called from other widgets",
    },
    defaultLayout: { x: 6, y: 10, w: 3, h: 4 },
  },

  LearningStrategies: {
    name: "Lernstrategien",
    component: () => import("./widgets/LearningStrategies"),
    router: () => import("./widgets/LearningStrategies/router"),
    dependencies: {
      stores: [],
      sharedState: ["courseid", "userid"],
      hasRouter: true,
      description:
        "Has own router for strategy navigation (#/strategies/:strategy)",
    },
    defaultLayout: { x: 9, y: 10, w: 3, h: 4 },
  },

  CourseOverview: {
    name: "Kursübersicht",
    component: () => import("./widgets/CourseOverview"),
    store: () => import("./widgets/CourseOverview/store"),
    dependencies: {
      stores: ["overview"],
      sharedState: ["courseid", "userid"],
      description: "Uses overview store for course structure and activities",
    },
    defaultLayout: { x: 0, y: 14, w: 6, h: 3 },
  },

  Deadlines: {
    name: "Termine",
    component: () => import("./widgets/Deadlines"),
    dependencies: {
      stores: [],
      sharedState: ["courseid", "userid", "logger"],
      description: "Self-contained widget, uses only logger from shared state",
    },
    defaultLayout: { x: 0, y: 17, w: 6, h: 3 },
  },

  TeacherActivity: {
    name: "Lehreraktivität",
    component: () => import("./widgets/TeacherActivity"),
    dependencies: {
      stores: [],
      sharedState: ["courseid"],
      description: "Self-contained widget showing teacher activity",
    },
    defaultLayout: { x: 6, y: 14, w: 6, h: 3 },
  },

  QuizStatistics: {
    name: "Quiz Statistiken",
    component: () => import("./widgets/QuizStatistics"),
    dependencies: {
      stores: ["overview"],
      sharedState: ["courseid", "userid"],
      description: "Uses overview store for quiz data",
    },
    defaultLayout: { x: 6, y: 17, w: 6, h: 3 },
  },
};

/**
 * Get all widgets
 */
export function getAllWidgets() {
  return Object.keys(widgetRegistry).map((key) => ({
    id: key,
    ...widgetRegistry[key],
  }));
}

/**
 * Get widget by ID
 */
export function getWidget(id) {
  return widgetRegistry[id];
}

/**
 * Get widgets that have routers
 */
export function getWidgetsWithRouters() {
  return Object.entries(widgetRegistry)
    .filter(([_, config]) => config.router)
    .map(([id, config]) => ({ id, ...config }));
}

/**
 * Get widgets that have stores
 */
export function getWidgetsWithStores() {
  return Object.entries(widgetRegistry)
    .filter(([_, config]) => config.store)
    .map(([id, config]) => ({ id, ...config }));
}

/**
 * Validate widget dependencies
 * Useful for debugging and ensuring all required stores are loaded
 */
export function validateWidgetDependencies(widgetId, availableStores) {
  const widget = widgetRegistry[widgetId];
  if (!widget) return { valid: false, error: "Widget not found" };

  const requiredStores = widget.dependencies?.stores || [];
  const missingStores = requiredStores.filter(
    (store) => !availableStores.includes(store),
  );

  return {
    valid: missingStores.length === 0,
    missing: missingStores,
    required: requiredStores,
  };
}
