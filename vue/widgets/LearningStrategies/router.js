/**
 * LearningStrategies Widget Router
 * Handles navigation for learning strategy pages
 */

// Export only the strategy route - the home route is handled by main router
const routes = [
  {
    path: "/strategies/:strategy",
    name: "strategy",
    component: () => import("../../App.vue"),
  },
];

export default routes;

