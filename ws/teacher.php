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
 * Teacher-related webservice methods
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

/**
 * Teacher webservice methods.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3_teacher_external extends external_api
{
    /**
     * Returns the parameters for getting all teachers of course.
     *
     * @return external_function_parameters Parameters for the function.
     */
    public static function get_all_teachers_of_course_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data.
     */
    public static function get_all_teachers_of_course_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets all teachers of a specific course.
     *
     * @param int $courseid Course ID.
     * @return array Array containing success flag and JSON-encoded teacher data.
     */
    public static function get_all_teachers_of_course($courseid) {
        global $DB;

        $context = context_course::instance($courseid);

        // Get users with teacher or editing teacher role.
        $teacherroles = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
             FROM {role_assignments} ra
             JOIN {user} u ON u.id = ra.userid
             JOIN {role} r ON r.id = ra.roleid
             WHERE ra.contextid = :contextid
             AND r.shortname IN ('teacher', 'editingteacher')
             AND u.deleted = 0",
            ['contextid' => $context->id]
        );

        return [
            'success' => true,
            'data' => json_encode(array_values($teacherroles)),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true.
     */
    public static function get_all_teachers_of_course_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the parameters for getting last access of teachers.
     *
     * @return external_function_parameters Parameters for the function.
     */
    public static function get_last_access_of_teachers_of_course_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data.
     */
    public static function get_last_access_of_teachers_of_course_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets last access time of teachers in a specific course.
     *
     * @param int $courseid Course ID.
     * @return array Array containing success flag and JSON-encoded teacher access data.
     */
    public static function get_last_access_of_teachers_of_course($courseid) {
        global $DB;

        $context = context_course::instance($courseid);

        $teacheraccess = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, ul.timeaccess as lastaccess
             FROM {role_assignments} ra
             JOIN {user} u ON u.id = ra.userid
             JOIN {role} r ON r.id = ra.roleid
             LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = :courseid2
             WHERE ra.contextid = :contextid
             AND r.shortname IN ('teacher', 'editingteacher')
             AND u.deleted = 0
             ORDER BY ul.timeaccess DESC",
            ['contextid' => $context->id, 'courseid2' => $courseid]
        );

        return [
            'success' => true,
            'data' => json_encode(array_values($teacheraccess)),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true.
     */
    public static function get_last_access_of_teachers_of_course_is_allowed_from_ajax() {
        return true;
    }
}
