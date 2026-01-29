<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Widget Manager for format_serial3
 *
 * Manages widget availability and settings for courses
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

defined('MOODLE_INTERNAL') || die();

/**
 * Widget Manager class
 *
 * Handles widget configuration, availability, and settings
 */
class widget_manager {
    /**
     * Get all available widgets with their metadata
     *
     * @return array Array of widget definitions
     */
    public static function get_available_widgets(): array {
        return [
            'ProgressChartAdaptive' => [
                'name' => get_string('widget_progresschartadaptive', 'format_serial3'),
                'description' => get_string('widget_progresschartadaptive_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['overview', 'recommendations', 'taskList'],
                'settings' => [],
            ],
            'IndicatorDisplay' => [
                'name' => get_string('widget_indicatordisplay', 'format_serial3'),
                'description' => get_string('widget_indicatordisplay_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['learnermodel'],
                'settings' => [],
            ],
            'Recommendations' => [
                'name' => get_string('widget_recommendations', 'format_serial3'),
                'description' => get_string('widget_recommendations_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['recommendations'],
                'settings' => [],
            ],
            'TaskList' => [
                'name' => get_string('widget_tasklist', 'format_serial3'),
                'description' => get_string('widget_tasklist_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['taskList'],
                'settings' => [],
            ],
            'LearningStrategies' => [
                'name' => get_string('widget_learningstrategies', 'format_serial3'),
                'description' => get_string('widget_learningstrategies_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => [],
                'settings' => [
                    'show_categories' => [
                        'type' => 'checkbox',
                        'label' => get_string('widget_learningstrategies_showcategories', 'format_serial3'),
                        'default' => 1,
                    ],
                ],
            ],
            'CourseOverview' => [
                'name' => get_string('widget_courseoverview', 'format_serial3'),
                'description' => get_string('widget_courseoverview_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['overview'],
                'settings' => [],
            ],
            'Deadlines' => [
                'name' => get_string('widget_deadlines', 'format_serial3'),
                'description' => get_string('widget_deadlines_desc', 'format_serial3'),
                'default_enabled' => true,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => [],
                'settings' => [
                    'days_ahead' => [
                        'type' => 'text',
                        'label' => get_string('widget_deadlines_daysahead', 'format_serial3'),
                        'default' => 30,
                    ],
                ],
            ],
            'TeacherActivity' => [
                'name' => get_string('widget_teacheractivity', 'format_serial3'),
                'description' => get_string('widget_teacheractivity_desc', 'format_serial3'),
                'default_enabled' => false,
                'requires_permission' => 'format/serial3:viewteacheractivity',
                'dependencies' => [],
                'settings' => [],
            ],
            'QuizStatistics' => [
                'name' => get_string('widget_quizstatistics', 'format_serial3'),
                'description' => get_string('widget_quizstatistics_desc', 'format_serial3'),
                'default_enabled' => false,
                'requires_permission' => 'format/serial3:viewdashboard',
                'dependencies' => ['overview'],
                'settings' => [],
            ],
        ];
    }

    /**
     * Get enabled widgets for a course
     *
     * @param int $courseid Course ID
     * @return array Array of enabled widget IDs
     */
    public static function get_enabled_widgets(int $courseid): array {
        $format = course_get_format($courseid);
        $settings = $format->get_format_options();

        if (isset($settings['enabled_widgets'])) {
            $enabled = json_decode($settings['enabled_widgets'], true);
            if (is_array($enabled)) {
                return $enabled;
            }
        }

        // Return default enabled widgets.
        $widgets = self::get_available_widgets();
        $enabled = [];
        foreach ($widgets as $id => $widget) {
            if ($widget['default_enabled']) {
                $enabled[] = $id;
            }
        }
        return $enabled;
    }

    /**
     * Check if a widget is enabled for a course
     *
     * @param int $courseid Course ID
     * @param string $widgetid Widget ID
     * @return bool True if enabled
     */
    public static function is_widget_enabled(int $courseid, string $widgetid): bool {
        $enabled = self::get_enabled_widgets($courseid);
        return in_array($widgetid, $enabled);
    }

    /**
     * Get widget settings for a course
     *
     * @param int $courseid Course ID
     * @param string $widgetid Widget ID
     * @return array Widget settings
     */
    public static function get_widget_settings(int $courseid, string $widgetid): array {
        global $DB;

        // Get settings from course_format_options table.
        $optionkey = 'widget_settings_' . $widgetid;
        $record = $DB->get_record('course_format_options', [
            'courseid' => $courseid,
            'format' => 'serial3',
            'name' => $optionkey,
        ]);

        if ($record && !empty($record->value)) {
            $widgetsettings = json_decode($record->value, true);
            if (is_array($widgetsettings)) {
                return $widgetsettings;
            }
        }

        // Return default settings.
        $widgets = self::get_available_widgets();
        if (isset($widgets[$widgetid]['settings'])) {
            $defaults = [];
            foreach ($widgets[$widgetid]['settings'] as $key => $setting) {
                $defaults[$key] = $setting['default'] ?? null;
            }
            return $defaults;
        }

        return [];
    }

    /**
     * Save enabled widgets for a course
     *
     * @param int $courseid Course ID
     * @param array $widgets Array of enabled widget IDs
     * @return bool Success
     */
    public static function save_enabled_widgets(int $courseid, array $widgets): bool {
        $format = course_get_format($courseid);
        $data = [
            'id' => $courseid,
            'enabled_widgets' => json_encode($widgets),
        ];
        return $format->update_course_format_options($data);
    }

    /**
     * Save widget settings for a course
     *
     * @param int $courseid Course ID
     * @param string $widgetid Widget ID
     * @param array $settings Widget settings
     * @return bool Success
     */
    public static function save_widget_settings(int $courseid, string $widgetid, array $settings): bool {
        global $DB;

        $optionkey = 'widget_settings_' . $widgetid;
        $value = json_encode($settings);

        // Check if record exists.
        $record = $DB->get_record('course_format_options', [
            'courseid' => $courseid,
            'format' => 'serial3',
            'name' => $optionkey,
        ]);

        if ($record) {
            // Update existing record.
            $record->value = $value;
            return $DB->update_record('course_format_options', $record);
        } else {
            // Insert new record.
            $record = new \stdClass();
            $record->courseid = $courseid;
            $record->format = 'serial3';
            $record->sectionid = 0;
            $record->name = $optionkey;
            $record->value = $value;
            return $DB->insert_record('course_format_options', $record) > 0;
        }
    }

    /**
     * Check if user can manage widgets
     *
     * @param int $courseid Course ID
     * @param int $userid User ID (0 for current user)
     * @return bool True if user can manage
     */
    public static function can_manage_widgets(int $courseid, int $userid = 0): bool {
        $context = \context_course::instance($courseid);
        return has_capability('moodle/course:update', $context, $userid);
    }
}
