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
 * Tasks webservice methods
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/analytics.php');

/**
 * External API for task list management.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3_tasks_external extends external_api
{
    /**
     * Interface to add new task list items to a user's task list
     */
    public static function create_task_parameters() {
        return new external_function_parameters([
            'course' => new external_value(PARAM_INT, 'Course ID'),
            'task' => new external_value(PARAM_RAW, 'Task description'),
            'completed' => new external_value(PARAM_BOOL, 'Task completion status'),
            'duedate' => new external_value(PARAM_TEXT, 'Task due date'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function create_task_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and new item ID
     */
    public static function create_task_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'id' => new external_value(PARAM_INT, 'ID of new item'),
        ]);
    }

    /**
     * Creates a new task item in the user's task list.
     *
     * @param int $course Course ID
     * @param string $task Task description
     * @param bool $completed Task completion status
     * @param string $duedate Task due date
     * @return array Array containing success flag and new item ID
     * @throws Exception When insert fails
     */
    public static function create_task($course, $task, $completed, $duedate) {
        global $DB, $USER;

        $record = new stdClass();
        $record->userid = (int)$USER->id;
        $record->course = (int)$course;
        $record->task = (string)($task);
        $record->duedate = strtotime($duedate);
        $record->timemodified = time();
        $record->completed = (int)$completed;

        $insertresult = $DB->insert_record("serial3_tasks", $record);

        if ($insertresult) {
            return [
                'success' => true,
                'id' => $insertresult,
            ];
        } else {
            throw new Exception('Failed to insert new item');
        }
    }

    /**
     * Interface to toggle a task item.
     *
     * @return external_function_parameters Parameters for updating a task
     */
    public static function update_task_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'user id'),
            'duedate' => new external_value(PARAM_RAW, 'task due date'),
            'completed' => new external_value(PARAM_RAW, 'completion status of task'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function update_task_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag
     */
    public static function update_task_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'success flag'),
            'data' => new external_value(PARAM_RAW, 'message for user'),
        ]);
    }

    /**
     * Updates an existing task item.
     *
     * @param int $id Task ID
     * @param string $duedate Task due date
     * @param mixed $completed Task completion status
     * @return array Array containing success flag and update result
     */
    public static function update_task($id, $duedate, $completed) {
        // update task status in database
        global $DB;

        $record = $DB->get_record('serial3_tasks', ['id' => (int)$id]);

        if ($record) {
            $record->completed = $completed;
            $record->duedate = strtotime($duedate);
            $success = $DB->update_record('serial3_tasks', $record);
        } else {
            $success = false;
        }

        // set success flag and message for response
        return [
            'success' => true,
            'data' => $success,
        ];
    }

    /**
     * Interface to delete a to-do item.
     *
     * @return external_function_parameters Parameters for deleting a task
     */
    public static function delete_task_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'user id'),
        ]);
    }

    /**
     * Indicates whether this external function can be called via AJAX.
     *
     * @return bool Always returns true
     */
    public static function delete_task_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag
     */
    public static function delete_task_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'success flag'),
        ]);
    }

    /**
     * Deletes a task item from the database.
     *
     * @param int $id Task ID to delete
     * @return array Array containing success flag
     */
    public static function delete_task($id) {
        global $DB;

        $DB->delete_records('serial3_tasks', ['id' => $id]);

        return [
            'success' => true,
        ];
    }

    /**
     * Interface to fetch all to-do items for a user.
     *
     * @return external_function_parameters Parameters for getting tasks
     */
    public static function get_tasks_parameters() {
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
    public static function get_tasks_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure Structure containing success flag and data
     */
    public static function get_tasks_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'data' => new external_value(PARAM_RAW, 'Data output'),
            ]
        );
    }

    /**
     * Retrieves all tasks for a specific user and course.
     *
     * @param int $userid User ID
     * @param int $course Course ID
     * @return array Array containing success flag and JSON-encoded tasks
     */
    public static function get_tasks($userid, $course) {
        global $DB;

        $res = $DB->get_records('serial3_tasks', ['userid' => (int)$userid, 'course' => (int)$course]);

        if (!$res) {
            $success = false;
        } else {
            $success = true;

            foreach ($res as &$todo) {
                if ($todo->duedate != 0) {
                    $todo->duedate = date('Y-m-d', $todo->duedate);
                }
            }
        }

        return [
            'success' => $success,
            'data' => json_encode($res),
        ];
    }
}
