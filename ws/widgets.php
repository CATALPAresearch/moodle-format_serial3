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
 * Widget settings webservice for format_serial3
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/../classes/widget_manager.php');

/**
 * Widget settings webservice class
 */
class format_serial3_widgets_external extends external_api {
    /**
     * Get enabled widgets for a course
     *
     * @param int $courseid Course ID
     * @return array Response with enabled widgets and settings
     */
    public static function get_widget_config($courseid) {
        global $USER;

        $course = get_course($courseid);
        require_login($course);

        $context = \context_course::instance($courseid);

        // Get enabled widgets
        $enabledwidgets = \format_serial3\widget_manager::get_enabled_widgets($courseid);

        // Get all widgets with their metadata
        $allwidgets = \format_serial3\widget_manager::get_available_widgets();

        // Build widget configuration
        $widgetconfig = [];
        $isteacher = has_capability('moodle/course:update', $context);

        foreach ($allwidgets as $widgetid => $widget) {
            // Convert widget ID to lowercase for frontend consistency
            $frontendid = strtolower($widgetid);

            // Teachers see all widgets, students only see enabled ones
            if ($isteacher || in_array($widgetid, $enabledwidgets)) {
                $widgetconfig[] = [
                    'id' => $frontendid,
                    'name' => $widget['name'],
                    'description' => isset($widget['description']) ? $widget['description'] : '',
                    'enabled' => in_array($widgetid, $enabledwidgets),
                ];
            }
        }

        return [
            'success' => true,
            'widgets' => $widgetconfig,
            'canManage' => $isteacher,
        ];
    }

    /**
     * Parameters for get_widget_config
     *
     * @return \external_function_parameters
     */
    public static function get_widget_config_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return \external_description
     */
    public static function get_widget_config_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'widgets' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_ALPHANUMEXT, 'Widget ID'),
                    'name' => new \external_value(PARAM_TEXT, 'Widget name'),
                    'description' => new \external_value(PARAM_TEXT, 'Widget description'),
                    'enabled' => new \external_value(PARAM_BOOL, 'Widget enabled'),
                ]),
                'Widget configurations',
                VALUE_OPTIONAL
            ),
            'canManage' => new \external_value(PARAM_BOOL, 'Can user manage widgets', VALUE_OPTIONAL),
            'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Save enabled widgets for a course
     *
     * @param int $courseid Course ID
     * @param array $widgets Array of widget IDs to enable
     * @return array Response with success status
     */
    public static function save_widget_config($courseid, $widgets) {
        global $USER;

        $course = get_course($courseid);
        require_login($course);

        $context = \context_course::instance($courseid);

        try {
            // Validate widget IDs
            $allwidgets = \format_serial3\widget_manager::get_available_widgets();
            $validwidgets = [];

            // Create a lowercase to PascalCase mapping
            $idmap = [];
            foreach ($allwidgets as $widgetid => $widget) {
                $idmap[strtolower($widgetid)] = $widgetid;
            }

            foreach ($widgets as $frontendid) {
                // Convert frontend ID back to backend ID
                $backendid = isset($idmap[$frontendid]) ? $idmap[$frontendid] : $frontendid;
                if (isset($allwidgets[$backendid])) {
                    $validwidgets[] = $backendid;
                }
            }

            // Save enabled widgets
            $success = \format_serial3\widget_manager::save_enabled_widgets($courseid, $validwidgets);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Widget configuration saved successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to save widget configuration',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parameters for save_widget_config
     *
     * @return \external_function_parameters
     */
    public static function save_widget_config_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'widgets' => new \external_multiple_structure(
                new \external_value(PARAM_ALPHANUMEXT, 'Widget ID')
            ),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return \external_description
     */
    public static function save_widget_config_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'message' => new \external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
