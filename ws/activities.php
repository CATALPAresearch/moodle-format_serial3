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
 * Activities-related webservice methods (assignments, quizzes, deadlines)
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

class format_serial3_activities_external extends external_api
{
    /**
     * Interface to fetch all quizzes and assignments
     */
    public static function get_assignments_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_assignments_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_assignments_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_assignments($course)
    {
        global $DB, $USER;

        $userid = $USER->id;

        $sql = "SELECT a.name, a.intro, a.allowsubmissionsfromdate, a.duedate, a.grade as max_grade, g.grade as user_grade, g.attemptnumber, cs.section,
				(SELECT COUNT(*) FROM {assign_submission} WHERE assignment = a.id AND userid = :userid) as user_attempts,
				(SELECT AVG(grade) FROM {assign_grades} WHERE assignment = a.id) as avg_grade,
				(SELECT COUNT(DISTINCT userid) FROM {assign_grades} WHERE assignment = a.id) as num_participants
				FROM {assign} a
				JOIN {assign_grades} g ON g.assignment = a.id
				JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = (SELECT id FROM {modules} WHERE name = 'assign')
				JOIN {course_sections} cs ON cs.id = cm.section
				WHERE a.course = :course AND g.userid = :userid2";

        $params = array('course' => $course, 'userid' => $userid, 'userid2' => $userid);
        $assignments = $DB->get_records_sql($sql, $params);


        return array(
            'success' => true,
            'data' => json_encode($assignments)
        );
    }


    /**
     * Interface to fetch all quizzes
     */
    public static function get_quizzes_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_quizzes_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_quizzes_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_quizzes($course)
    {
        global $DB, $USER;

        $userid = $USER->id;

        $sql = "SELECT q.name, q.intro, q.timeopen, q.timeclose, q.sumgrades, q.grade as max_grade, g.grade as user_grade, cs.section,
            (SELECT AVG(grade) FROM {quiz_grades} WHERE quiz = q.id) as avg_grade,
            (SELECT COUNT(*) FROM {quiz_attempts} WHERE quiz = q.id AND userid = :userid) as user_attempts,
            (SELECT COUNT(DISTINCT userid) FROM {quiz_grades} WHERE quiz = q.id) as num_participants
            FROM {quiz} q
            JOIN {quiz_grades} g ON g.quiz = q.id
            JOIN {course_modules} cm ON cm.instance = q.id AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
            JOIN {course_sections} cs ON cs.id = cm.section
            WHERE q.course = :course AND g.userid = :user";

        $params = array('course' => $course, 'userid' => $userid, 'user' => $userid);
        $quizrecords = $DB->get_records_sql($sql, $params);

        return array(
            'success' => true,
            'data' => json_encode($quizrecords)
        );
    }

    /**
     * Get assignment and quiz deadlines
     */
    public static function get_deadlines_parameters()
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_deadlines_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_deadlines_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_deadlines($courseid)
    {
        global $DB;

        try {
            $sql = "SELECT a.id, cm.id AS coursemoduleid, a.allowsubmissionsfromdate AS timestart, a.name, a.duedate AS timeclose, 'assignment' AS type
				FROM {assign} a
				JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = (SELECT id FROM {modules} WHERE name = 'assign')
				WHERE a.course = :course AND a.duedate != 0
				UNION
				SELECT q.id, cm.id AS coursemoduleid, q.timeopen AS timestart, q.name, q.timeclose, 'quiz' AS type
				FROM {quiz} q
				JOIN {course_modules} cm ON cm.instance = q.id AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
				WHERE q.course = :courseid AND q.timeclose != 0";

            $params = array('courseid' => $courseid, 'course' => $courseid);
            $data = $DB->get_records_sql($sql, $params);

            return array(
                'success' => true,
                'data' => json_encode($data)
            );
        } catch (Exception $e) {
            error_log("Error fetching deadlines: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Interface to fetch get the missed and total number of assignments and quizzes
     */
    public static function get_missed_activities_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_missed_activities_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_missed_activities_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_missed_activities($course)
    {
        global $DB, $USER;

        $sql = "SELECT
    			COUNT(CASE WHEN s.id IS NULL THEN 1 END) AS num_missed_assignments,
    			COUNT(*) AS total_assignments
				FROM {assign} a
				LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = :userid
				WHERE a.course = :course";
                //AND a.allowsubmissionsfromdate < UNIX_TIMESTAMP() AND a.duedate < UNIX_TIMESTAMP()

        $params = array('course' => $course, 'userid' => (int)$USER->id);
        $missedAssignments = $DB->get_records_sql($sql, $params);

        return array(
            'success' => true,
            'data' => json_encode($missedAssignments)
        );
    }
}
