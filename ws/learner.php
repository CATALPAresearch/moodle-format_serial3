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
 * Learner-related webservice methods
 *
 * @package    format_serial3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

class format_serial3_learner_external extends external_api
{
    /**
     * Sets the learner goal for each user and course.
     */
    public static function set_learner_goal_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
            'goal' => new external_value(PARAM_TEXT, 'The users learning goal.'),
        ]);
    }

    public static function set_learner_goal_is_allowed_from_ajax()
    {
        return true;
    }

    public static function set_learner_goal_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success variable'),
            )
        );
    }

    public static function set_learner_goal($course, $goal)
    {
        global $DB, $USER;

        $userid = (int)$USER->id;

        $record = $DB->get_record('serial3_learner_goal', array('userid' => $userid, 'course' => $course));

        if ($record) {
            $record->goal = $goal;
            $success = $DB->update_record('serial3_learner_goal', $record);
        } else {
            $record = new stdClass();
            $record->userid = $userid;
            $record->course = (int)$course;
            $record->goal = $goal;
            $success = $DB->insert_record('serial3_learner_goal', $record);
        }

        return array(
            'success' => $success,
        );
    }


    /**
     * Gets the learner goal for each user and course.
     */
    public static function get_learner_goal_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_learner_goal_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_learner_goal_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_learner_goal($course)
    {
        global $DB, $USER;

        $userid = (int)$USER->id;

        $goal = $DB->get_field('serial3_learner_goal', 'goal', array('userid' => $userid, 'course' => $course));

        return array(
            'success' => true,
            'data' => json_encode($goal),
        );
    }


    /**
     * Set users understaning of course activity
     */
    public static function set_user_understanding_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
            'activityid' => new external_value(PARAM_TEXT, 'id of activity'),
            'rating' => new external_value(PARAM_INT, 'user understanding'),
        ]);
    }

    public static function set_user_understanding_is_allowed_from_ajax()
    {
        return true;
    }

    public static function set_user_understanding_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function set_user_understanding($course, $activityid, $rating)
    {
        global $DB, $USER;

        $userid = (int)$USER->id;

        $params = [
            'userid' => $userid,
            'course' => $course,
            'activityid' => (int)$activityid,
        ];

        $record = $DB->get_record('serial3_overview', $params);

        if ($record) {
            $record->rating = $rating;
            $DB->update_record('serial3_overview', $record);
        } else {
            $record = new stdClass();
            $record->userid = (int)$userid;
            $record->course = (int)$course;
            $record->activityid = (int)$activityid;
            $record->rating = (int)$rating;
            $success = $DB->insert_record('serial3_overview', $record);
        }

        return array(
            'success' => true,
            'data' => json_encode($success)
        );
    }

    /**
     * Get users understanding of course activity
     */
    public static function get_user_understanding_parameters()
    {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_user_understanding_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_user_understanding_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_user_understanding($course)
    {
        global $DB, $USER;

        $params = [
            'userid' => (int)$USER->id,
            'course' => (int)$course,
        ];

        $res = $DB->get_records('serial3_overview', $params);

        // Convert to indexed array for JSON encoding
        $data = $res ? array_values($res) : [];

        return array(
            'success' => true,
            'data' => json_encode($data)
        );
    }
}
