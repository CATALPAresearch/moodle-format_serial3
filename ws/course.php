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
 * Course structure webservice methods
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

class format_serial3_course_external extends external_api
{
    /**
     * Returns the parameters for getting added or changed course resources.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function get_added_or_changed_course_resources_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_added_or_changed_course_resources_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets recently added or modified course resources.
     *
     * @param int $courseid Course ID
     * @return array Array containing success flag and JSON-encoded resource data
     */
    public static function get_added_or_changed_course_resources($courseid) {
        global $DB;

        // Get recently added or modified course modules
        $resources = $DB->get_records_sql(
            "SELECT cm.id, cm.added, m.name as modname, cm.instance
             FROM {course_modules} cm
             JOIN {modules} m ON m.id = cm.module
             WHERE cm.course = :courseid
             AND cm.deletioninprogress = 0
             ORDER BY cm.added DESC
             LIMIT 50",
            ['courseid' => $courseid]
        );

        // Get more details for each resource.
        $detailedresources = [];
        foreach ($resources as $resource) {
            $resource->url = '';
            $resource->filename = '';

            // Try to get the resource name from the specific module table.
            $tablename = $resource->modname;
            if ($DB->get_manager()->table_exists($tablename)) {
                $moduledata = $DB->get_record($tablename, ['id' => $resource->instance]);
                if ($moduledata && isset($moduledata->name)) {
                    $resource->filename = $moduledata->name;
                }
            }

            $detailedresources[] = $resource;
        }

        return [
            'success' => true,
            'data' => json_encode($detailedresources),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function get_added_or_changed_course_resources_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the parameters for getting deleted course resources.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function get_deleted_course_resources_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_deleted_course_resources_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets recently deleted course resources from log.
     *
     * @param int $courseid Course ID
     * @return array Array containing success flag and JSON-encoded deleted resource data
     */
    public static function get_deleted_course_resources($courseid) {
        global $DB;

        // Get deleted course modules from log.
        $deletedresources = $DB->get_records_sql(
            "SELECT l.id, l.timecreated, l.objectid, l.other
             FROM {logstore_standard_log} l
             WHERE l.courseid = :courseid
             AND l.action = 'deleted'
             AND l.target = 'course_module'
             ORDER BY l.timecreated DESC
             LIMIT 50",
            ['courseid' => $courseid]
        );

        $processedresources = [];
        foreach ($deletedresources as $resource) {
            $resource->filename = '';
            if (!empty($resource->other)) {
                $other = json_decode($resource->other);
                if (isset($other->name)) {
                    $resource->filename = $other->name;
                } else if (isset($other->modulename)) {
                    $resource->filename = $other->modulename;
                }
            }
            $processedresources[] = $resource;
        }

        return [
            'success' => true,
            'data' => json_encode($processedresources),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function get_deleted_course_resources_is_allowed_from_ajax() {
        return true;
    }
}
