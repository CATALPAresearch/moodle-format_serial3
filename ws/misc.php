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
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

/**
 * External API for miscellaneous webservice methods.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3_misc_external extends external_api
{
    /**
     * Returns the parameters for getting calendar data.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function getcalendar_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'course id'),
            ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing calendar data
     */
    public static function getcalendar_returns() {
        return new external_single_structure(
            [
                'data' => new external_value(PARAM_RAW, 'data'),
            ]
        );
    }

    /**
     * Gets calendar events for a specific course.
     *
     * @param int $data Course ID
     * @return array Array containing JSON-encoded calendar events
     */
    public static function getcalendar($data) {
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
        return ['data' => json_encode($data)];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function getcalendar_is_allowed_from_ajax() {
        return true;
    }




    /**
     * Returns the parameters for getting survey data.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function get_surveys_parameters() {
        // VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED.
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'course id'),
            ]
        );
    }

    /**
     * Gets survey completion data for the individual user.
     *
     * @param int $courseid Course ID
     * @param int $moduleid Module ID
     * @return array Array containing success flag and JSON-encoded survey data
     */
    public static function get_surveys($courseid, $moduleid) {
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
                "userid" => (int)$USER->id,
            ]
        );

        return [
            'success' => true,
            'data' => json_encode($res),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_surveys_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function get_surveys_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the parameters for reading reflections.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function reflectionread_parameters() {
        // VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED.
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'course id'),
            ]
        );
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function reflectionread_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function reflectionread_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, ''),
            ]
        );
    }

    /**
     * Reads all reflections for a user in a specific course.
     *
     * @param int $data Course ID
     * @return array Array containing success flag and JSON-encoded reflection data
     */
    public static function reflectionread($data) {
        global $DB, $USER;
        $debug = [];
        $userid = (int)$USER->id;
        $courseid = $data;
        $transaction = $DB->start_delegated_transaction();
        $res = $DB->get_records_sql(
            "SELECT * FROM {serial3_reflections} WHERE courseid=:course AND userid=:user ORDER BY timecreated ASC",
            ["course" => (int)$courseid, "user" => (int)$userid]
        );
        $transaction->allow_commit();

        // TODO: json_encode($debug).

        return [
            'success' => true,
            'data' => json_encode($res),
        ];
    }


    /**
     * Returns the parameters for creating a reflection.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function reflectioncreate_parameters() {
        // VALUE_REQUIRED, VALUE_OPTIONAL, or VALUE_DEFAULT. If not mentioned, a value is VALUE_REQUIRED.
        return new external_function_parameters(
            [
                'data' =>
                new external_single_structure(
                    [
                        'course' => new external_value(PARAM_INT, 'course id'),
                        'section' => new external_value(PARAM_INT, 'section id'),
                        'reflection' => new external_value(PARAM_TEXT, 'reflection text submitted by the learner'),
                    ]
                ),
            ]
        );
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function reflectioncreate_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function reflectioncreate_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, ''),
            ]
        );
    }

    /**
     * Creates a new reflection entry for a user.
     *
     * @param array $data Array containing course, section, and reflection text
     * @return array Array containing success flag and JSON-encoded data
     */
    public static function reflectioncreate($data) {
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

        return [
            'success' => true,
            'data' => json_encode($data),
        ];
    }



    /**
     * Returns the parameters for saving dashboard settings.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function save_dashboard_settings_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'if of user'),
            'course' => new external_value(PARAM_INT, 'id of course'),
            'settings' => new external_value(PARAM_TEXT, 'layout settings', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function save_dashboard_settings_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_TEXT, 'Data output'),
            ]
        );
    }

    /**
     * Saves dashboard layout settings for a user.
     *
     * @param int $userid User ID
     * @param int $course Course ID
     * @param string $settings JSON-encoded layout settings
     * @return array Array containing success flag and JSON-encoded data
     */
    public static function save_dashboard_settings($userid, $course, $settings) {
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

        return [
            'success' => ($record !== false),
            'data' => json_encode($params),
        ];
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function save_dashboard_settings_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the parameters for getting dashboard settings.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function get_dashboard_settings_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'user id'),
            'course' => new external_value(PARAM_INT, 'id of course'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function get_dashboard_settings_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_dashboard_settings_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Retrieves dashboard settings for a specific user and course.
     *
     * @param int $userid User ID
     * @param int $course Course ID
     * @return array Array containing success flag and JSON-encoded settings
     */
    public static function get_dashboard_settings($userid, $course) {
        global $DB;

        $result = $DB->get_record_sql(
            "SELECT settings
            FROM {serial3_dashboard_settings}
            WHERE
            	userid=:userid AND
            	course=:course",
            [
                "course" => (int)$course,
                "userid" => (int)$userid,
            ]
        );

        return [
            'success' => true,
            'data' => json_encode($result),
        ];
    }


    /**
     * Returns the parameters for setting rule response.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function set_rule_response_parameters() {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'course id'),
            'action_id' => new external_value(PARAM_TEXT, 'id of the rule action'),
            'response_type' => new external_value(PARAM_TEXT, 'user response to a rule action'),
            'user_response' => new external_value(PARAM_RAW, 'user response to a rule action'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function set_rule_response_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag
     */
    public static function set_rule_response_returns() {
        return new external_single_structure(['success' => new external_value(PARAM_BOOL, 'Success Variable')]);
    }

    /**
     * Sets user response to a rule action.
     *
     * @param int $courseid Course ID
     * @param string $actionid Rule action ID
     * @param string $responsetype Type of response
     * @param mixed $userresponse User's response data
     * @return array Array containing success flag
     */
    public static function set_rule_response($courseid, $actionid, $responsetype, $userresponse) {
        global $DB, $USER;
        $date = new DateTime();
        $record = new stdClass();
        $record->user_id = (int)$USER->id;
        $record->course_id = (int)$courseid;
        $record->action_id = $actionid;
        $record->response_type = $responsetype;
        $record->response = $userresponse;
        $record->timecreated = $date->getTimestamp();

        // There was an error inserting the record.

        return [
            'success' => true,
        ];
    }

    /**
     * Returns the parameters for getting recommendations.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function get_recommendations_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'course' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function get_recommendations_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_recommendations_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success Variable'),
            'data' => new external_value(PARAM_RAW, 'Data output'),
        ]);
    }

    /**
     * Gets personalized recommendations for a user in a specific course.
     *
     * @param int $userid User ID
     * @param int $course Course ID
     * @return array Array containing success flag and JSON-encoded recommendations
     */
    public static function get_recommendations($userid, $course) {
        global $DB;

        // Placeholder implementation - returns empty array for now.
        // This can be extended to implement actual recommendation logic.
        $recommendations = [];

        return [
            'success' => true,
            'data' => json_encode($recommendations),
        ];
    }

    /**
     * Returns the parameters for logging client data.
     *
     * @return external_function_parameters Parameters for the function
     */
    public static function logger_parameters() {
        return new external_function_parameters(
            [
                'data' =>
                new external_single_structure(
                    [
                        'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_OPTIONAL),
                        'utc' => new external_value(PARAM_INT, 'utc time', VALUE_OPTIONAL),
                        'action' => new external_value(PARAM_TEXT, 'action', VALUE_OPTIONAL),
                        'entry' => new external_value(PARAM_RAW, 'log data', VALUE_OPTIONAL),
                    ]
                ),
            ]
        );
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function logger_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function logger_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, ''),
                'data' => new external_value(PARAM_RAW, ''),
            ]
        );
    }

    /**
     * Collects log data from the client and stores it in the database.
     *
     * @param array $data Array containing courseid, utc time, action, and log entry
     * @return array Array containing success flag and JSON-encoded result
     */
    public static function logger($data) {
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
        $res = $DB->insert_records("logstore_standard_log", [$r]);
        $transaction->allow_commit();

        return [
            'success' => true,
            'data' => json_encode($res),
        ];
    }
}
