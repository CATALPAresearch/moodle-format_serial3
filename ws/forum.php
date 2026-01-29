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
 * Forum-related webservice methods
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

/**
 * Forum webservice methods.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3_forum_external extends external_api
{
    /**
     * Returns the parameters for getting forum posts.
     *
     * @return external_function_parameters Parameters for the function.
     */
    public static function get_forum_posts_parameters() {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true.
     */
    public static function get_forum_posts_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data.
     */
    public static function get_forum_posts_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets forum post statistics for a course.
     *
     * @param int $course Course ID.
     * @return array Array containing success flag and JSON-encoded forum post data.
     */
    public static function get_forum_posts($course) {
        global $DB, $USER;

        $userid = (int)$USER->id;

        $sql = "SELECT 
			(SELECT COUNT(*) 
			 FROM {forum_posts} fp 
			 JOIN {forum_discussions} fd ON fd.id = fp.discussion 
			 WHERE fd.course = :courseid AND fp.userid = :userid) AS user_posts,
			COUNT(*) AS total_posts,
			COUNT(*) / COALESCE(NULLIF(COUNT(DISTINCT fp.userid), 0)) AS avg_posts_per_person,
			(SELECT COUNT(*) 
			 FROM {forum_posts} fp 
			 JOIN {forum_discussions} fd ON fd.id = fp.discussion 
			 GROUP BY fp.userid 
			 ORDER BY COUNT(*) DESC 
			 LIMIT 1) AS max_user_posts,
			(SELECT COUNT(*) 
			 FROM {forum_posts} fp 
			 JOIN {forum_discussions} fd ON fd.id = fp.discussion 
			 GROUP BY fp.userid 
			 ORDER BY COUNT(*) ASC 
			 LIMIT 1) AS min_user_posts
		FROM {forum_posts} fp 
		JOIN {forum_discussions} fd ON fd.id = fp.discussion 
		WHERE fd.course = :courseid1 AND fp.userid IS NOT NULL
        ;";

        $params = ['courseid' => (int)$course, 'courseid1' => (int)$course, 'userid' => $userid];

        $result = $DB->get_records_sql($sql, $params);
        $result = [];
        return [
            'success' => true,
            'data' => json_encode($result),
        ];
    }

    /**
     * Returns the parameters for getting new forum discussions.
     *
     * @return external_function_parameters Parameters for the function.
     */
    public static function get_new_forum_discussions_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data.
     */
    public static function get_new_forum_discussions_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets new forum discussions created by teachers.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return array Array containing success flag and JSON-encoded discussion data.
     */
    public static function get_new_forum_discussions($courseid, $userid) {
        global $DB;

        $context = context_course::instance($courseid);

        // Get forum discussions created by teachers
        $discussions = $DB->get_records_sql(
            "SELECT fd.id, fd.name, fd.userid, fd.timemodified, fd.timestart,
                    u.firstname, u.lastname, f.name as forumname
             FROM {forum_discussions} fd
             JOIN {forum} f ON f.id = fd.forum
             JOIN {user} u ON u.id = fd.userid
             JOIN {role_assignments} ra ON ra.userid = u.id
             JOIN {role} r ON r.id = ra.roleid
             WHERE fd.course = :courseid
             AND ra.contextid = :contextid
             AND r.shortname IN ('teacher', 'editingteacher')
             ORDER BY fd.timemodified DESC
             LIMIT 50",
            ['courseid' => $courseid, 'contextid' => $context->id]
        );

        return [
            'success' => true,
            'data' => json_encode(array_values($discussions)),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true.
     */
    public static function get_new_forum_discussions_is_allowed_from_ajax() {
        return true;
    }
}
