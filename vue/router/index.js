import { createRouter, createWebHashHistory } from "vue-router";
import learningStrategiesRoutes from "../widgets/LearningStrategies/router";

// Base routes
const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("../App.vue"),
  },
  // Widget routes are merged here
  ...learningStrategiesRoutes,
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
