# Widget Management System

Widgets are elements containing data visualisiations or learning support instruments that can be arranged together in a dashboard.  
Teachers can enable/disable dashboard widgets via a modal interface (slider icon in MenuBar). From the set of enables widgets students can choose which widgets they want to see at certain position on the dashboard.

Configuration stored in `course_format_options` table.

## Components

- **Backend:** `widget_manager.php`, `ws/widgets.php`, `db/services.php`
- **Frontend:** `MenuBar.vue` (modal), `App.vue` (filtering), `widgets/registry.js` (auto-discovery)
- **Auto-Discovery:** `widgets/registry.js` automatically registers all widget components

## Registered Widgets

1. ProgressChartAdaptive - Course overview
2. IndicatorDisplay - Learning goals
3. Recommendations - Personalized feedback
4. TaskList - Task management
5. LearningStrategies - Strategy browser
6. CourseOverview - Course structure
7. Deadlines - Upcoming deadlines
8. TeacherActivity - Teacher activity
9. QuizStatistics - Quiz performance

## Auto-Discovery

**Frontend:** `require.context()` finds all `index.js` files  
**Backend:** `DirectoryIterator` scans widget folder names

**Widget Structure:**

```
vue/widgets/
├── TaskList/
│   ├── TaskList.vue      # Component
│   └── index.js          # Metadata: { name, w, h }
└── registry.js
```

## Adding a Widget

**1. Create widget folder:**

```bash
vue/widgets/MyWidget/
├── MyWidget.vue          # Main component
└── index.js              # { name, w, h }
```

**2. Define metadata in `index.js`:**

```javascript
export default {
  name: "My Widget Display Name",
  w: 6, // Grid width
  h: 4, // Grid height
};
```

**3. Add language string `widget_mywidget` in `lang/en/format_serial3.php`**
