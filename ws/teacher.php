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
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

class format_serial3_teacher_external extends external_api
{
    // Get all teachers of course
    public static function get_all_teachers_of_course_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID')
            )
        );
    }

    public static function get_all_teachers_of_course_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_all_teachers_of_course($courseid)
    {
        global $DB;

        $context = context_course::instance($courseid);
        
        // Get users with teacher or editing teacher role
        $teacherRoles = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
             FROM {role_assignments} ra
             JOIN {user} u ON u.id = ra.userid
             JOIN {role} r ON r.id = ra.roleid
             WHERE ra.contextid = :contextid
             AND r.shortname IN ('teacher', 'editingteacher')
             AND u.deleted = 0",
            array('contextid' => $context->id)
        );

        return array(
            'success' => true,
            'data' => json_encode(array_values($teacherRoles)),
        );
    }

    public static function get_all_teachers_of_course_is_allowed_from_ajax()
    {
        return true;
    }

    // Get last access of teachers in course
    public static function get_last_access_of_teachers_of_course_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID')
            )
        );
    }

    public static function get_last_access_of_teachers_of_course_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_last_access_of_teachers_of_course($courseid)
    {
        global $DB;

        $context = context_course::instance($courseid);
        
        $teacherAccess = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, ul.timeaccess as lastaccess
             FROM {role_assignments} ra
             JOIN {user} u ON u.id = ra.userid
             JOIN {role} r ON r.id = ra.roleid
             LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = :courseid2
             WHERE ra.contextid = :contextid
             AND r.shortname IN ('teacher', 'editingteacher')
             AND u.deleted = 0
             ORDER BY ul.timeaccess DESC",
            array('contextid' => $context->id, 'courseid2' => $courseid)
        );

        return array(
            'success' => true,
            'data' => json_encode(array_values($teacherAccess)),
        );
    }

    public static function get_last_access_of_teachers_of_course_is_allowed_from_ajax()
    {
        return true;
    }
}
