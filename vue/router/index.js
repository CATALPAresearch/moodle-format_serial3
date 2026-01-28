import { createRouter, createWebHashHistory } from "vue-router";

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("../App.vue"),
  },
  {
    path: "/strategies/:strategy",
    name: "strategy",
    component: () => import("../App.vue"),
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    // If navigating to a strategy, scroll to the learning strategies widget
    if (to.params.strategy) {
      return new Promise((resolve) => {
        // Wait a bit for the component to mount and render
        setTimeout(() => {
          const element = document.getElementById("widget-LearningStrategies");
          if (element) {
            resolve({
              el: element,
              behavior: "smooth",
              top: 120, // offset from top
            });
          } else {
            resolve({ top: 0 });
          }
        }, 100);
      });
    }

    // Otherwise use saved position or scroll to top
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  },
});

export default router;
