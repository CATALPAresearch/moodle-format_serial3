# Mock Data System

This directory contains mock data for demonstration and testing purposes. The mock data system allows teachers (moderators) to simulate different student behavior scenarios without needing real student data.

## Overview

The mock data system provides:

- **5 distinct scenarios** representing typical and edge-case student behaviors
- **8 widget-specific data files** with realistic data for each scenario
- **Teacher-facing UI** (dropdown in MenuBar) to switch between scenarios
- **Automatic widget refresh** when scenarios change

## Scenarios

### 1. Excellent Student (`excellent`)

**Description:** "High-performing student with excellent completion rates and grades"

- 95%+ activity completion
- Grades consistently 85-100%
- Few or no overdue items
- Regular engagement with all activities
- High understanding metrics

**Use Case:** Show what an ideal student journey looks like

### 2. Average Student (`average`)

**Description:** "Typical student with moderate completion and average grades"

- 50-70% activity completion
- Grades around 70-80%
- Some overdue items
- Inconsistent engagement
- Moderate understanding metrics

**Use Case:** Show realistic middle-of-the-road performance

### 3. Struggling Student (`struggling`)

**Description:** "Student with low completion rates who needs support"

- 20-30% activity completion
- Grades 40-60% (some failing)
- Multiple overdue items
- Low engagement
- Poor understanding metrics
- Many high-priority recommendations

**Use Case:** Demonstrate intervention opportunities and support needs

### 4. Procrastinator (`procrastinator`)

**Description:** "Student who completes work but waits until the last minute"

- Late but eventual completion
- Grades 60-75%
- Multiple items due soon/urgent
- Cramming behavior pattern
- Sporadic understanding

**Use Case:** Show time management issues and last-minute behavior

### 5. Edge Cases (`edge_cases`)

**Description:** "Test data with boundary conditions and unusual values"

- Empty strings, null values
- Extremely long text fields (300+ characters)
- Dates in distant past (2020) or future (2099)
- Zero values and maximum values
- Grades exceeding maximums
- Missing or incomplete data

**Use Case:** Test widget robustness and error handling

## Widget Data Files

Each widget has its own JSON file with data structured for all 5 scenarios:

| Widget            | File                         | Data Structure                                         |
| ----------------- | ---------------------------- | ------------------------------------------------------ |
| Course Overview   | `CourseOverview.json`        | `completions[]`, `reflections[]`                       |
| Progress Chart    | `ProgressChartAdaptive.json` | `courseData{}`, `proficiency`, `progressUnderstanding` |
| Task List         | `TaskList.json`              | `tasks[]`                                              |
| Recommendations   | `Recommendations.json`       | `recommendations[]`                                    |
| Deadlines         | `Deadlines.json`             | `calendar[]`, `assignments[]`                          |
| Indicator Display | `IndicatorDisplay.json`      | `proficiency`, `userGrade`, `thresholds{}`             |
| Quiz Statistics   | `QuizStatistics.json`        | `quizzes[]`, `assignments[]`                           |
| Teacher Activity  | `TeacherActivity.json`       | `teachers[]`, `resources[]`, `forums[]`                |

**Note:** LearningStrategies widget does not use mock data.

## File Structure

```
mock-data/
├── README.md                       # This file
├── scenarios.json                  # Scenario definitions with descriptions
├── CourseOverview.json             # Completion and reflection data
├── ProgressChartAdaptive.json      # Progress and understanding metrics
├── TaskList.json                   # Student tasks and deadlines
├── Recommendations.json            # Learning recommendations
├── Deadlines.json                  # Calendar events and assignments
├── IndicatorDisplay.json           # Learning goal indicators
├── QuizStatistics.json             # Quiz and assignment grades
└── TeacherActivity.json            # Teacher interaction data
```

## Usage for Teachers

### Enabling Mock Data

1. **Access the dropdown:** As a teacher/moderator, you'll see a flask icon (🧪) in the MenuBar
2. **Choose a scenario:** Click the flask icon and select from:
   - Excellent Student
   - Average Student
   - Struggling Student
   - Procrastinator
   - Edge Cases (Testing)
3. **Watch widgets update:** All widgets automatically refresh with the scenario data
4. **Visual indicator:** The flask icon has an orange background when mock data is active

### Disabling Mock Data

- Click the flask icon and select "Disable Mock Data"
- Widgets will reload with real student data

### Best Practices

- **Demonstrations:** Use "Excellent" or "Average" scenarios for positive examples
- **Training:** Use "Struggling" to show intervention features
- **Testing:** Use "Edge Cases" to verify widget stability
- **Compare scenarios:** Switch between scenarios to show different student patterns

## Implementation for Developers

### Using Mock Data in Widgets

Widgets should check for mock data in their `loadData()` methods:

```javascript
import mockDataHelper from "../scripts/mockDataHelper";

export default {
  methods: {
    async loadData() {
      // Use mock data helper
      const result = await mockDataHelper.loadWidgetData(
        this.$store,
        "WidgetName", // Must match JSON filename without .json
        async () => {
          // Real data loading logic here
          const response = await fetch("/path/to/real/api");
          return {
            success: true,
            data: await response.json(),
            isMockData: false,
          };
        },
      );

      if (result.success) {
        // Apply data to component
        this.someData = result.data.someField;

        if (result.isMockData) {
          console.log("Using mock data");
        }
      }
    },
  },
};
```

### Alternative: Manual Implementation

```javascript
async loadData() {
  if (this.$store.state.mockDataEnabled) {
    const mockData = await this.$store.dispatch('loadMockData', {
      widgetName: 'WidgetName'
    });

    if (mockData) {
      // Apply mock data
      this.someData = mockData.someField;
      return;
    }
  }

  // Load real data
  // ... existing API calls
}
```

### Adding New Mock Data

To add mock data for a new widget:

1. **Create JSON file:** `vue/mock-data/YourWidget.json`
2. **Structure:** Include all 5 scenarios with realistic data
3. **Schema:**
   ```json
   {
     "excellent": {
       /* data */
     },
     "average": {
       /* data */
     },
     "struggling": {
       /* data */
     },
     "procrastinator": {
       /* data */
     },
     "edge_cases": {
       /* data */
     }
   }
   ```
4. **Update widget:** Implement mock data loading in widget's `loadData()` method

## Data Characteristics

### Excellent Scenario

- Completion: 90-100%
- Grades: 85-100
- Overdue items: 0-1
- Understanding: High (2-3 on 0-3 scale)
- Engagement: Daily/regular

### Average Scenario

- Completion: 50-70%
- Grades: 65-80
- Overdue items: 2-3
- Understanding: Moderate (1-2 on 0-3 scale)
- Engagement: Weekly/sporadic

### Struggling Scenario

- Completion: 20-40%
- Grades: 30-60
- Overdue items: 4-6
- Understanding: Low (0-1 on 0-3 scale)
- Engagement: Rare/minimal

### Procrastinator Scenario

- Completion: 60-80% (late)
- Grades: 60-75
- Overdue items: 0 (caught up), but many due soon
- Understanding: Inconsistent (varies)
- Engagement: Bursts before deadlines

### Edge Cases Scenario

- Null values, empty strings
- Extreme dates (past: 2000, future: 2050+)
- Zero values
- Maximum/over-maximum values
- Very long text (300+ chars)
- Missing fields

## Technical Details

### Vuex Store Integration

**State:**

```javascript
mockDataEnabled: false,      // Boolean: is mock data active?
mockDataScenario: 'average'  // String: current scenario
```

**Actions:**

```javascript
// Load mock data for a widget
this.$store.dispatch("loadMockData", { widgetName, scenario });

// Toggle mock data on/off
this.$store.dispatch("toggleMockData");

// Set specific scenario
this.$store.dispatch("setMockScenario", "excellent");
```

**Getters:**

```javascript
this.$store.getters.getMockDataEnabled();
this.$store.getters.getMockDataScenario();
```

### Data Loading

Mock data is loaded via fetch:

```
/course/format/serial3/vue/mock-data/{widgetName}.json
```

The store action:

1. Fetches the JSON file
2. Extracts data for current scenario
3. Returns data to widget
4. Falls back to real data if fetch fails

## Testing Checklist

When testing mock data:

- [ ] Dropdown appears for moderators only
- [ ] Flask icon turns orange when mock data active
- [ ] Each scenario loads successfully
- [ ] All widgets update with scenario data
- [ ] Edge cases scenario doesn't break UI
- [ ] Switching scenarios refreshes widgets
- [ ] Disabling mock data returns to real data
- [ ] No console errors during scenario switches
- [ ] Data structure matches widget expectations
- [ ] Visual indicators show correct scenario

## Troubleshooting

### Mock data not loading

- Check browser console for fetch errors
- Verify JSON file exists in `/vue/mock-data/`
- Confirm widget name matches JSON filename exactly
- Check Moodle cache (purge if needed)

### Dropdown not appearing

- Verify user has moderator permissions
- Check `isModerator` in store state
- Rebuild Vue app: `npm run build` in `/vue/`

### Widgets not updating

- Ensure widget listens to 'refreshWidgets' event
- Check widget's `loadData()` method checks `mockDataEnabled`
- Verify Vuex store properly configured

### Data structure mismatch

- Compare JSON structure to widget's expected format
- Check console for data access errors
- Update JSON file to match widget requirements

## Future Enhancements

Potential improvements:

- Add more scenarios (international student, part-time student, etc.)
- Per-widget scenario selection
- Scenario presets for specific training goals
- Export/import custom scenarios
- Randomized data generation
- Time-based progression simulation

## Maintenance

When updating mock data:

1. Keep all 5 scenarios in sync with structure changes
2. Ensure edge cases cover new fields with boundary values
3. Update this README with new fields or widgets
4. Test all scenarios after changes
5. Document any breaking changes

---

**Last Updated:** 2024
**Version:** 1.0
**Maintainer:** Development Team
