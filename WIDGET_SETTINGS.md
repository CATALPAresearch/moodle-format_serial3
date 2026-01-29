# Widget Management System

## Overview

Teachers can enable/disable dashboard widgets via a modal interface (slider icon in MenuBar). Configuration stored in `course_format_options` table.

## Components

- **Backend:** `widget_manager.php`, `ws/widgets.php`, `db/services.php`
- **Frontend:** `MenuBar.vue` (modal), `App.vue` (filtering), `communication.js`

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

## Adding a Widget

**1. Register in `widget_manager.php`:**

```php
'MyWidget' => [
    'name' => get_string('widget_mywidget', 'format_serial3'),
    'description' => get_string('widget_mywidget_desc', 'format_serial3'),
    'default_enabled' => true,
],
```

**2. Add language strings in `lang/en/format_serial3.php`**

**3. Create Vue component in `vue/widgets/MyWidget/`**

**4. Import in `App.vue`:**

```javascript
import MyWidget from "./widgets/MyWidget";
components: {
  MyWidget: MyWidget.component;
}
```

**5. Add to widget map in `App.vue` `isWidgetEnabled()`:**

```javascript
const widgetMap = { MyWidget: "mywidget" }; // PascalCase → lowercase
```

## Usage

**Teachers:** Slider icon → check/uncheck widgets → Save

**Check programmatically:**

```php
use format_serial3\widget_manager;
$enabled = widget_manager::is_widget_enabled($courseid, 'TaskList');
```

## Storage

```sql
courseid | format  | name            | value
42       | serial3 | enabled_widgets | ["TaskList","ProgressChartAdaptive"]
```

## API

**widget_manager.php:**

- `get_available_widgets()` - All widget definitions
- `get_enabled_widgets($courseid)` - Enabled widget IDs
- `is_widget_enabled($courseid, $widgetid)` - Check if enabled
- `save_enabled_widgets($courseid, $widgets)` - Save configuration

**Webservice:**

- `format_serial3_get_widget_config` - Get config
- `format_serial3_save_widget_config` - Save config

## Notes

- PascalCase IDs in backend (PHP), lowercase in frontend (Vue)
- Bidirectional ID mapping in `ws/widgets.php`
- Filtering in `App.vue` `created()` hook
- Changes require page reload
