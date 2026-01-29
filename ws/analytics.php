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
 * Analytics, statistics and overview webservice methods
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * Gets metadata for a course including user and context information.
 *
 * @param int $courseid Course ID
 * @return stdClass Object containing course and user metadata
 */
function get_meta($courseid) {
    try {
        global $USER, $COURSE;
        $obj = new stdClass();
        $obj->course = new stdClass();
        $obj->course->id = (int)$courseid;
        require_login($obj->course->id);
        $obj->course->context = context_course::instance($obj->course->id);
        $obj->course->global = $COURSE;
        $obj->user = new stdClass();
        $obj->user->id = $USER->id;
        $obj->user->loggedin = isloggedin();
        $obj->user->siteadmin = is_siteadmin($USER->id);
        $obj->user->enrolled = is_enrolled($obj->course->context, $USER->id);
        $obj->user->guest = is_guest($obj->course->context, $USER->id);
        $obj->user->viewing = is_viewing($obj->course->context, $USER->id);
        $obj->user->roles = [];
        $obj->user->global = $USER;
        $roles = get_user_roles($obj->course->context, $USER->id);
        $obj->user->roles_raw = $roles;
        $obj->user->student = false;
        $obj->user->teacher = false;
        $obj->user->editingteacher = false;
        $obj->user->coursecreator = false;
        $obj->user->manager = false;
        foreach ($roles as $key => $value) {
            if (isset($value->shortname)) {
                switch ($value->shortname) {
                    case 'teacher':
                        $obj->user->teacher = true;
                        break;
                    case 'editingteacher':
                        $obj->user->editingteacher = true;
                        break;
                    case 'coursecreator':
                        $obj->user->coursecreator = true;
                        break;
                    case 'manager':
                        $obj->user->manager = true;
                        break;
                    case 'student':
                        $obj->user->student = true;
                        break;
                }
                $obj->user->roles[] = $value->shortname;
            }
        }
        return $obj;
    } catch (Exception $ex) {
        return null;
    }
}

/**
 * Analytics webservice methods.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3_analytics_external extends external_api
{
    /**
     * Returns the parameters for overview function.
     *
     * @return external_function_parameters Parameters for the function.
     */
    public static function overview_parameters() {
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
     * @return bool Always returns true.
     */
    public static function overview_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data.
     */
    public static function overview_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Gets overview data for a course including activities and completions.
     *
     * @param int $data Course ID.
     * @return array Array containing success flag and JSON-encoded overview data.
     */
    public static function overview($data) {
        global $CFG, $DB, $USER, $COURSE;
        $userid = (int)$USER->id;
        $courseid = $data;
        $debug = [];
        $meta = get_meta($courseid);

        // Step 1: Obtain all course activities.
        $modinfo = get_fast_modinfo($courseid, $userid);
        $sections = $modinfo->get_sections();
        $activities = [];
        foreach ($modinfo->instances as $module => $instances) {
            $modulename = get_string('pluginname', $module);
            foreach ($instances as $index => $cm) {
                $cmx = get_coursemodule_from_id($module, $cm->id);
                $url = $CFG->wwwroot . '/mod/' . $module . '/view.php?id=' . $cmx->id;
                $activities[] = [
                    'type'       => $module,
                    'modulename' => $modulename,
                    'id'         => $cm->id,
                    'instance'   => $cm->instance,
                    'name'       => format_string($cm->name),
                    'expected'   => $cm->completionexpected,
                    'section'    => $cm->sectionnum,
                    'sectionname' => get_section_name($courseid, $cm->sectionnum),
                    'position'   => array_search($cm->id, $sections[$cm->sectionnum]),
                    'url'        => $url,
                    'context'    => $cm->context,
                    'icon'       => $cm->get_icon_url(),
                    'available'  => $cm->available,
                    'completion' => 0,
                    'visible'     => $cm->visible,
                    'rating'     => 0,
                ];
            }
        }

        // Step 1B: Expand Safran activity for containing tasks.
        // TODO.
        /*
        foreach ($activities as $activity) {
            if($activity['type'] == 'safran'){
                // $query = "SELECT id as safranid,  FROM {safran_q_attempt} WHERE questionid = :questionid".
                $query = "SELECT
                        id as safranid,
                    FROM {safran_question}
                    WHERE safranid = :safranid";
                $resultset = $DB->get_recordset_sql($query, [
                    "safranid" => $activity["instance"]
                ]);
            }
        }
        */

        // Step 2: Get completions.
        $completions = [];
        $completion = new completion_info($COURSE);
        // $completion->is_enabled($cm) We need to check this.
        $cm = new stdClass();

        foreach ($activities as $activity) {
            $cm->id = $activity['id'];
            $activitycompletion = $completion->get_data($cm, true, $userid);
            $completions[$activity['id']] = $activity;
        }

        // Step 3: Get scores.
        $queryactivities = [
            'assign' => "SELECT
                    m.name activity,
                    a.id activity_id,
                    cm.id module_id,
                    cm.section,
                    (SELECT count(*) FROM {course_modules} cmm
                     JOIN {modules} m ON m.id = cmm.module
                     WHERE m.name = 'assign' AND cmm.course = cm.course AND cmm.section = cm.section) count,
                    a.grade max_score,
                    ag.grade achieved_score,
                    asub.timemodified  submission_time,
                    ag.timemodified grading_time
                FROM {assign} a
                LEFT JOIN {assign_grades} ag ON a.id = ag.assignment
                LEFT JOIN {assign_submission} asub ON a.id = asub.assignment
                LEFT JOIN {course_modules} cm ON a.id = cm.instance
                LEFT JOIN {modules} m ON m.id = cm.module
                WHERE
                    a.course = :courseid AND
                    ag.userid = :userid AND
                    asub.status = 'submitted' AND
                    asub.latest = 1 AND
                    m.name = 'assign'
                ;",
            'quiz' => "SELECT
                    m.name activity,
                    q.id activity_id,
                    cm.id module_id,
                    cm.section,
                    (select count(*) from {course_modules} cmm
                     JOIN {modules} m ON m.id = cmm.module
                     WHERE m.name = 'quiz' AND cmm.course=cm.course AND cmm.section = cm.section) count,
                    q.grade max_score,
                    qsub.sumgrades*10 achieved_score,
                    qsub.timemodified  submission_time,
                    qsub.timemodified grading_time
                FROM {quiz} q
                LEFT JOIN {quiz_attempts} qsub ON q.id = qsub.quiz
                LEFT JOIN {course_modules} cm ON q.id = cm.instance
                LEFT JOIN {modules} m ON m.id = cm.module
                WHERE
                    q.course = :courseid AND
                    qsub.userid = :userid AND
                    qsub.state = 'finished' AND
                    m.name = 'quiz'
            ;",
            'longpage' => "SELECT DISTINCT
                    m.name activity,
                    l.id activity_id,
                    cm.id module_id,
                    cm.section,
                    COUNT(DISTINCT lrp.section) complete,
                    AVG(lrp.sectioncount) count,
                    '0' AS max_score,
                    '0' AS achieved_score,
                    MAX(lrp.timemodified) AS submission_time,
                    '0' AS grading_time
                    FROM {longpage} l
                    JOIN {longpage_reading_progress} lrp ON l.id = lrp.longpageid
                    RIGHT JOIN {course_modules} cm ON l.id = cm.instance
                    RIGHT JOIN {modules} m ON m.id = cm.module
                    WHERE
                    l.course = :courseid AND
                    lrp.userid= :userid AND
                    m.name = 'longpage'
                    Group by m.name, l.id, cm.id, cm.section
            ;",
            'hypervideo' => "SELECT DISTINCT
                    m.name activity,
                    h.id activity_id,
                    cm.id module_id,
                    cm.section,
                    SUM(hl.duration) count,
                    COUNT(DISTINCT hl.values) * 2 complete, -- static parameter - attention
                    '0' AS max_score,
                    '0' AS achieved_score,
                    MAX(hl.timemodified) AS submission_time,
                    '0' AS grading_time
                FROM {hypervideo} h
                JOIN {hypervideo_log} hl ON h.id = hl.hypervideo
                RIGHT JOIN {course_modules} cm ON h.id = cm.instance
                RIGHT JOIN {modules} m ON m.id = cm.module
                WHERE
                    h.course = :courseid AND
                    hl.userid = :userid AND
                    hl.actions = 'playback' AND
                    m.name = 'hypervideo'
                GROUP BY m.name, h.id, cm.id, cm.section
            ;",
        ];
        /*
        SELECT distinct
        m.name activity,
        l.id activity_id,
        cm.id module_id,
        cm.section,
        COUNT(distinct lrp.section) / AVG(lrp.sectioncount) count,
        '0' AS max_score,
        '0' AS achieved_score,
        MAX(lrp.timemodified) AS submission_time,
        '0' AS grading_time
        FROM mdl_longpage l
        JOIN mdl_longpage_reading_progress lrp ON l.id = lrp.longpageid
        RIGHT JOIN mdl_course_modules cm ON l.id = cm.instance
        RIGHT JOIN mdl_modules m ON m.id = cm.module
        WHERE
        lrp.userid=2 AND
        -- lrp.longpageid=1 AND
        m.name = 'longpage'
        Group by cm.id




        ;
        ;
        */

        $params = ['courseid' => $courseid, 'userid' => $userid];
        $res = [];
        foreach ($queryactivities as $moduletype => $query) {
            try {
                $resultset = $DB->get_recordset_sql($query, $params);
                foreach ($resultset as $key => $value) {
                    if (!property_exists('value', 'activity')) {
                        $res[] = $value;
                    }
                }
            } catch (Exception $e) {
                // There was an error processing this module type.
                $debug[] = $e;
            }
        }
        $debug[] = "---resultset----";
        $debug[] = $res;

        // Step 4: Add scores to completion.
        foreach ($completions as $sec => $activity) {
            foreach ($res as $type => $item) {
                // Debug item.
                if ($activity['type'] == $item->activity && $activity['instance'] == $item->activity_id) {
                    $completions[$sec]['achieved_score'] = $item->achieved_score;
                    $completions[$sec]['max_score'] = $item->max_score;
                    $completions[$sec]['count'] = $item->count;
                    $completions[$sec]['submission_time'] = $item->submission_time;
                    $completions[$sec]['name'] = $activity['type'];

                    if (isset($item->complete)) {
                        $completions[$sec]['complete'] = $item->complete;
                    }

                    $debug[] = $completions[$sec];
                }
            }
        }

        return [
            'success' => true,
            'data' => json_encode([
                'debug' => json_encode($debug),
                'completions' => json_encode($completions),
            ]),
        ];
    }
}
