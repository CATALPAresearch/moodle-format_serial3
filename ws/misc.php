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
 * Miscellaneous webservice methods
 *
 * @package    format_serial3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

class format_serial3_misc_external extends external_api
{
    /**
     * Get calendar data
     */

    public static function getcalendar_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id')
            )
        );
    }
    public static function getcalendar_returns()
    {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_RAW, 'data')
            )
        );
    }
    public static function getcalendar($data)
    {
        global $CFG, $DB, $USER;
        $transaction = $DB->start_delegated_transaction();
        $cid = (int)$data;
        $uid = (int)$USER->id;
        $sql = '
            SELECT * FROM ' . $CFG->prefix . 'event
            WHERE (' . $CFG->prefix . 'event.eventtype = \'site\') 
            OR (' . $CFG->prefix . 'event.eventtype = \'user\' AND ' . $CFG->prefix . 'event.userid = ' . $uid . ')
            OR (' . $CFG->prefix . 'event.eventtype = \'group\'
                AND ' . $CFG->prefix . 'event.courseid = ' . $cid . '
                AND ' . $CFG->prefix . 'event.groupid in 
                (SELECT ' . $CFG->prefix . 'groups.id 
                    FROM ' . $CFG->prefix . 'groups
                    INNER JOIN ' . $CFG->prefix . 'groups_members
                    ON ' . $CFG->prefix . 'groups.id = ' . $CFG->prefix . 'groups_members.groupid
                WHERE ' . $CFG->prefix . 'groups_members.userid = ' . $uid . ')
            )
            OR (' . $CFG->prefix . 'event.eventtype = \'course\' AND ' . $CFG->prefix . 'event.courseid = ' . $cid . ')
            OR (' . $CFG->prefix . 'event.eventtype = \'category\' AND ' . $CFG->prefix . 'event.categoryid in
   		        (SELECT ' . $CFG->prefix . 'course_categories.id
                    FROM ' . $CFG->prefix . 'course_categories
                    INNER JOIN ' . $CFG->prefix . 'course
                    ON ' . $CFG->prefix . 'course_categories.id = ' . $CFG->prefix . 'course.category
                WHERE ' . $CFG->prefix . 'course.id = ' . $cid . ')
            )
            ORDER BY ' . $CFG->prefix . 'event.timestart ASC';
        $data = $DB->get_records_sql($sql);
        $transaction->allow_commit();
        return array('data' => json_encode($data));
    }
    public static function getcalendar_is_allowed_from_ajax()
    {
        return true;
    }




    /**
     * Interface to get survey data of the individual user
     */
    public static function get_surveys_parameters()
    {
        //  VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'course id')
            )
        );
    }
    public static function get_surveys($courseid, $moduleid)
    {
        global $DB, $USER;

        $res = $DB->get_record_sql(
            "SELECT qr.submitted 
            FROM {questionnaire_response} qr
            JOIN {course_modules} cm ON qr.questionnaireid = cm.instance
            WHERE
            cm.id=:moduleid AND
            cm.course=:courseid AND
            qr.userid=:userid AND 
            qr.complete='y'",
            [
                "courseid" => (int)$courseid,
                "moduleid" => (int)$moduleid,
                "userid" => (int)$USER->id
            ]
        );

        return array(
            'success' => true,
            'data' => json_encode($res)
        );
    }
    public static function get_surveys_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }
    public static function get_surveys_is_allowed_from_ajax()
    {
        return true;
    }

    /**
     * Reflections
     **/
    public static function reflectionRead_parameters()
    {
        //  VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
            )
        );
    }

    public static function reflectionRead_is_allowed_from_ajax()
    {
        return true;
    }

    public static function reflectionRead_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, '')
            )
        );
    }
    public static function reflectionRead($data)
    {
        global $DB, $USER;
        $debug = [];
        $userid = (int)$USER->id;
        $courseid = $data;
        $transaction = $DB->start_delegated_transaction();
        $res = $DB->get_records_sql(
            "SELECT * FROM {serial3_reflections} WHERE courseid=:course AND userid=:user ORDER BY timecreated ASC",
            array("course" => (int)$courseid, "user" => (int)$userid)
        );
        $transaction->allow_commit();

        // TODO json_encode($debug)

        return array(
            'success' => true,
            'data' => json_encode($res)
        );
    }


    public static function reflectionCreate_parameters()
    {
        //  VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED
        return new external_function_parameters(
            array(
                'data' =>
                new external_single_structure(
                    array(
                        'course' => new external_value(PARAM_INT, 'course id'),
                        'section' => new external_value(PARAM_INT, 'section id'),
                        'reflection' => new external_value(PARAM_TEXT, 'reflection text submitted by the learner')
                    )
                )
            )
        );
    }

    public static function reflectionCreate_is_allowed_from_ajax()
    {
        return true;
    }

    public static function reflectionCreate_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, '')
            )
        );
    }
    public static function reflectionCreate($data)
    {
        global $CFG, $DB, $USER, $COURSE;
        $debug = [];
        $userid = (int)$USER->id;
        $date = date_create();

        $r = new stdClass();
        $r->userid = (int)$userid;
        $r->courseid = (int)$data['course'];
        $r->section = $data['section'];
        $r->reflection = $data['reflection'];
        $r->timecreated = date_timestamp_get($date);
        $r->timemodified = date_timestamp_get($date);

        $transaction = $DB->start_delegated_transaction();
        $res = $DB->insert_record("serial3_reflections", $r);
        $transaction->allow_commit();

        return array(
            'success' => true,
            'data' => json_encode($data)
        );
    }



    /**
     * Interface to save dashboard layout settings for a user
     */
    public static function save_dashboard_settings_parameters()
    {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'if of user'),
            'course' => new external_value(PARAM_INT, 'id of course'),
            'settings' => new external_value(PARAM_TEXT, 'layout settings', VALUE_OPTIONAL)
        ]);
    }

    public static function save_dashboard_settings_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_TEXT, 'Data output')
            )
        );
    }

    public static function save_dashboard_settings($userid, $course, $settings)
    {
        global $DB;

        $params = [
            'userid' => $userid,
            'course' => $course,
            'settings' => $settings,
        ];

        $record = $DB->get_record('serial3_dashboard_settings', ['userid' => $userid, 'course' => $course]);

        if ($record) {
            $record->settings = $settings;
            $DB->update_record('serial3_dashboard_settings', $record);
        } else {
            $record = new stdClass();
            $record->userid = $userid;
            $record->course = $course;
            $record->settings = $settings;
            $DB->insert_record('serial3_dashboard_settings', $record);
        }

        return array(
            'success' => ($record !== false),
            'data' => json_encode($params)
        );
    }

    public static function save_dashboard_settings_is_allowed_from_ajax()
    {
        return true;
    }

    /**
     * Interface to get dashboard settings for a user
     */
    public static function get_dashboard_settings_parameters()
    {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'user id'),
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    public static function get_dashboard_settings_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_dashboard_settings_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output')
            )
        );
    }

    public static function get_dashboard_settings($userid, $course)
    {
        global $DB;

        $result = $DB->get_record_sql(
            "SELECT settings
            FROM {serial3_dashboard_settings}
            WHERE
            	userid=:userid AND
            	course=:course",
            [
                "course" => (int)$course,
                "userid" => (int)$userid
            ]
        );

        return array(
            'success' => true,
            'data' => json_encode($result),
        );
    }


    //set_rule_response
    public static function set_rule_response_parameters()
    {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'course id'),
            'action_id' => new external_value(PARAM_TEXT, 'id of the rule action'),
            'response_type' => new external_value(PARAM_TEXT, 'user response to a rule action'),
            'user_response' => new external_value(PARAM_RAW, 'user response to a rule action'),
        ]);
    }

    public static function set_rule_response_is_allowed_from_ajax()
    {
        return true;
    }
    public static function set_rule_response_returns()
    {
        return new external_single_structure(['success' => new external_value(PARAM_BOOL, 'Success Variable')]);
    }
    public static function set_rule_response($course_id, $action_id, $response_type, $user_response)
    {
        global $DB, $USER;
        $date = new DateTime();
        $record = new stdClass();
        $record->user_id = (int)$USER->id;
        $record->course_id = (int)$course_id;
        $record->action_id = $action_id;
        $record->response_type = $response_type;
        $record->response = $user_response;
        $record->timecreated = $date->getTimestamp();

        //$DB->insert_record('ari_response_rule_action', $record);
        
        return array(
            'success' => true
        );
    }

    /**
     * Get recommendations
     */
    public static function get_recommendations_parameters()
    {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'course' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function get_recommendations_is_allowed_from_ajax()
    {
        return true;
    }

    public static function get_recommendations_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success Variable'),
            'data' => new external_value(PARAM_RAW, 'Data output')
        ]);
    }

    public static function get_recommendations($userid, $course)
    {
        global $DB;

        // Placeholder implementation - returns empty array for now
        // This can be extended to implement actual recommendation logic
        $recommendations = [];

        return array(
            'success' => true,
            'data' => json_encode($recommendations)
        );
    }

    /**
     * Collects log data from the client
     */
    public static function logger_parameters()
    {
        return new external_function_parameters(
            array(
                'data' =>
                new external_single_structure(
                    array(
                        'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_OPTIONAL),
                        'utc' => new external_value(PARAM_INT, 'utc time', VALUE_OPTIONAL),
                        'action' => new external_value(PARAM_TEXT, 'action', VALUE_OPTIONAL),
                        'entry' => new external_value(PARAM_RAW, 'log data', VALUE_OPTIONAL)
                    )
                )
            )
        );
    }

    public static function logger_is_allowed_from_ajax()
    {
        return true;
    }

    public static function logger_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, '')
            )
        );
    }

    public static function logger($data)
    {
        global $CFG, $DB, $USER;

        $r = new stdClass();
        $r->name = 'format_serial3';
        $r->component = 'format_serial3';
        $r->eventname = '\format_serial3\event\\' . $data['action'];
        $r->action = $data['action'];
        $r->target = 'course_format';
        $r->objecttable = 'serial3';
        $r->objectid = 0;
        $r->crud = 'r';
        $r->edulevel = 2;
        $r->contextid = 120;
        $r->contextlevel = 70;
        $r->contextinstanceid = 86;
        $r->userid = $USER->id;
        $r->courseid = (int)$data['courseid'];
        $r->anonymous = 0;
        $r->other = $data['entry'];
        $r->timecreated = $data['utc'];
        $r->origin = 'web';
        $r->ip = $_SERVER['REMOTE_ADDR'];

        $transaction = $DB->start_delegated_transaction();
        $res = $DB->insert_records("logstore_standard_log", array($r));
        $transaction->allow_commit();

        return array(
            'success' => true,
            'data' => json_encode($res)
        );
    }
}

