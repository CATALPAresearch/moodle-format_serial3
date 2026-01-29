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
 * Privacy Subsystem implementation for format_serial3.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for format_serial3.
 *
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_userlist_provider, \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'serial3_tasks',
            [
                'userid' => 'privacy:metadata:serial3_tasks:userid',
                'task' => 'privacy:metadata:serial3_tasks:task',
                'completed' => 'privacy:metadata:serial3_tasks:completed',
                'duedate' => 'privacy:metadata:serial3_tasks:duedate',
                'timemodified' => 'privacy:metadata:serial3_tasks:timemodified',
            ],
            'privacy:metadata:serial3_tasks'
        );

        $collection->add_database_table(
            'serial3_overview',
            [
                'userid' => 'privacy:metadata:serial3_overview:userid',
                'activityid' => 'privacy:metadata:serial3_overview:activityid',
                'rating' => 'privacy:metadata:serial3_overview:rating',
            ],
            'privacy:metadata:serial3_overview'
        );

        $collection->add_database_table(
            'serial3_learner_goal',
            [
                'userid' => 'privacy:metadata:serial3_learner_goal:userid',
                'goal' => 'privacy:metadata:serial3_learner_goal:goal',
                'timemodified' => 'privacy:metadata:serial3_learner_goal:timemodified',
            ],
            'privacy:metadata:serial3_learner_goal'
        );

        $collection->add_database_table(
            'serial3_dashboard_settings',
            [
                'userid' => 'privacy:metadata:serial3_dashboard_settings:userid',
                'settings' => 'privacy:metadata:serial3_dashboard_settings:settings',
            ],
            'privacy:metadata:serial3_dashboard_settings'
        );

        $collection->add_database_table(
            'serial3_reflections',
            [
                'userid' => 'privacy:metadata:serial3_reflections:userid',
                'reflection' => 'privacy:metadata:serial3_reflections:reflection',
                'timecreated' => 'privacy:metadata:serial3_reflections:timecreated',
                'timemodified' => 'privacy:metadata:serial3_reflections:timemodified',
            ],
            'privacy:metadata:serial3_reflections'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get all course contexts where the user has serial3 data.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 WHERE EXISTS (
                       SELECT 1 FROM {serial3_tasks} WHERE userid = :userid1 AND course = c.id
                 ) OR EXISTS (
                       SELECT 1 FROM {serial3_overview} WHERE userid = :userid2 AND course = c.id
                 ) OR EXISTS (
                       SELECT 1 FROM {serial3_learner_goal} WHERE userid = :userid3 AND course = c.id
                 ) OR EXISTS (
                       SELECT 1 FROM {serial3_dashboard_settings} WHERE userid = :userid4 AND course = c.id
                 ) OR EXISTS (
                       SELECT 1 FROM {serial3_reflections} WHERE userid = :userid5 AND courseid = c.id
                 )";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
            'userid4' => $userid,
            'userid5' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $params = ['courseid' => $context->instanceid];

        $sql = "SELECT userid FROM {serial3_tasks} WHERE course = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {serial3_overview} WHERE course = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {serial3_learner_goal} WHERE course = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {serial3_dashboard_settings} WHERE course = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {serial3_reflections} WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;

            // Export tasks.
            $tasks = $DB->get_records('serial3_tasks', ['userid' => $userid, 'course' => $courseid]);
            if (!empty($tasks)) {
                $data = [];
                foreach ($tasks as $task) {
                    $data[] = (object)[
                        'task' => $task->task,
                        'completed' => $task->completed ? get_string('yes') : get_string('no'),
                        'duedate' => $task->duedate ? transform::datetime($task->duedate) : '-',
                        'timemodified' => transform::datetime($task->timemodified),
                    ];
                }
                writer::with_context($context)->export_data([get_string('pluginname', 'format_serial3'), 'tasks'], (object)['tasks' => $data]);
            }

            // Export overview ratings.
            $overview = $DB->get_records('serial3_overview', ['userid' => $userid, 'course' => $courseid]);
            if (!empty($overview)) {
                $data = [];
                foreach ($overview as $item) {
                    $data[] = (object)[
                        'activityid' => $item->activityid,
                        'rating' => $item->rating,
                    ];
                }
                writer::with_context($context)->export_data([get_string('pluginname', 'format_serial3'), 'overview'], (object)['ratings' => $data]);
            }

            // Export learner goal.
            $goal = $DB->get_record('serial3_learner_goal', ['userid' => $userid, 'course' => $courseid]);
            if ($goal) {
                writer::with_context($context)->export_data([get_string('pluginname', 'format_serial3'), 'goal'], (object)[
                    'goal' => $goal->goal,
                    'timemodified' => $goal->timemodified ? transform::datetime($goal->timemodified) : '-',
                ]);
            }

            // Export dashboard settings.
            $settings = $DB->get_record('serial3_dashboard_settings', ['userid' => $userid, 'course' => $courseid]);
            if ($settings) {
                writer::with_context($context)->export_data([get_string('pluginname', 'format_serial3'), 'dashboard'], (object)[
                    'settings' => $settings->settings,
                ]);
            }

            // Export reflections.
            $reflections = $DB->get_records('serial3_reflections', ['userid' => $userid, 'courseid' => $courseid]);
            if (!empty($reflections)) {
                $data = [];
                foreach ($reflections as $reflection) {
                    $data[] = (object)[
                        'section' => $reflection->section,
                        'reflection' => $reflection->reflection,
                        'timecreated' => transform::datetime($reflection->timecreated),
                        'timemodified' => transform::datetime($reflection->timemodified),
                    ];
                }
                writer::with_context($context)->export_data([get_string('pluginname', 'format_serial3'), 'reflections'], (object)['reflections' => $data]);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $courseid = $context->instanceid;

        $DB->delete_records('serial3_tasks', ['course' => $courseid]);
        $DB->delete_records('serial3_overview', ['course' => $courseid]);
        $DB->delete_records('serial3_learner_goal', ['course' => $courseid]);
        $DB->delete_records('serial3_dashboard_settings', ['course' => $courseid]);
        $DB->delete_records('serial3_reflections', ['courseid' => $courseid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;

            $DB->delete_records('serial3_tasks', ['userid' => $userid, 'course' => $courseid]);
            $DB->delete_records('serial3_overview', ['userid' => $userid, 'course' => $courseid]);
            $DB->delete_records('serial3_learner_goal', ['userid' => $userid, 'course' => $courseid]);
            $DB->delete_records('serial3_dashboard_settings', ['userid' => $userid, 'course' => $courseid]);
            $DB->delete_records('serial3_reflections', ['userid' => $userid, 'courseid' => $courseid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $courseid = $context->instanceid;
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('serial3_tasks', "userid $usersql AND course = :course", $userparams + ['course' => $courseid]);
        $DB->delete_records_select('serial3_overview', "userid $usersql AND course = :course", $userparams + ['course' => $courseid]);
        $DB->delete_records_select('serial3_learner_goal', "userid $usersql AND course = :course", $userparams + ['course' => $courseid]);
        $DB->delete_records_select('serial3_dashboard_settings', "userid $usersql AND course = :course", $userparams + ['course' => $courseid]);
        $DB->delete_records_select('serial3_reflections', "userid $usersql AND courseid = :course", $userparams + ['course' => $courseid]);
    }
}
