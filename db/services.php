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
 * Web service function definitions for format_serial3
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
	'format_serial3_overview' => [
			'classname'   => 'format_serial3_analytics_external',
			'methodname'  => 'overview',
			'classpath'   => 'course/format/serial3/ws/analytics.php',
			'description' => 'Obtain the plugin name',
			'type'        => 'read',
			'ajax'        => true
	],
	'format_serial3_reflectionread' => [
			'classname'   => 'format_serial3_misc_external',
			'methodname'  => 'reflectionRead',
			'classpath'   => 'course/format/serial3/ws/misc.php',
			'description' => 'Read reflection data',
			'type'        => 'read',
			'ajax'        => true
	],
	'format_serial3_reflectioncreate' => [
			'classname'   => 'format_serial3_misc_external',
			'methodname'  => 'reflectionCreate',
			'classpath'   => 'course/format/serial3/ws/misc.php',
			'description' => 'Create reflection entry',
			'type'        => 'read',
			'ajax'        => true
	],
	'format_serial3_get_surveys' => [
			'classname'   => 'format_serial3_misc_external',
			'methodname'  => 'get_surveys',
			'classpath'   => 'course/format/serial3/ws/misc.php',
			'description' => 'Get survey data',
			'type'        => 'read',
			'ajax'        => true
	],
	'format_serial3_getcalendar' => [
			'classname'   => 'format_serial3_misc_external',
			'methodname'  => 'getcalendar',
			'classpath'   => 'course/format/serial3/ws/misc.php',
			'description' => 'Get the calendar data from moodle native calendar',
			'type'        => 'read',
			'ajax'        => true
	],
	'format_serial3_create_task' => [
		'classname' 		=> 'format_serial3_tasks_external',
		'methodname' 		=> 'create_task',
		'classpath' 		=> 'course/format/serial3/ws/tasks.php',
		'description' 		=> 'Adds a new item to the user\'s task list',
		'type' 				=> 'write',
		'loginrequired' 	=> true,
		'ajax' 				=> true,
	],
	'format_serial3_update_task' => [
		'classname' 		=> 'format_serial3_tasks_external',
		'methodname' 		=> 'update_task',
		'classpath' 		=> 'course/format/serial3/ws/tasks.php',
		'description' 		=> 'Updates the task list item',
		'type' 				=> 'write',
		'loginrequired'		=> true,
		'ajax' 				=> true,
	],
	'format_serial3_delete_task' => [
		'classname'			=> 'format_serial3_tasks_external',
		'methodname' 		=> 'delete_task',
		'classpath' 		=> 'course/format/serial3/ws/tasks.php',
		'description' 		=> 'Removes the given item from the task list',
		'type' 				=> 'write',
		'loginrequired' 	=> true,
		'ajax' 				=> true,
	],
	'format_serial3_get_tasks' => [
		'classname'			=> 'format_serial3_tasks_external',
		'methodname' 		=> 'get_tasks',
		'classpath'			=> 'course/format/serial3/ws/tasks.php',
		'description' 		=> 'Gets task list items',
		'type' 				=> 'read',
		'loginrequired' 	=> true,
		'ajax' 				=> true,
	],
	'format_serial3_save_dashboard_settings' => [
		'classname' 		=> 'format_serial3_misc_external',
		'methodname'		=> 'save_dashboard_settings',
		'classpath' 		=> 'course/format/serial3/ws/misc.php',
		'description' 		=> 'Saves users dashboard configurations',
		'type' 				=> 'write',
		'loginrequired' 	=> true,
		'ajax' 				=> true,
	],
	'format_serial3_get_dashboard_settings' => [
		'classname' 		=> 'format_serial3_misc_external',
		'methodname' 		=> 'get_dashboard_settings',
		'classpath' 		=> 'course/format/serial3/ws/misc.php',
		'description' 		=> 'Fetch users dashboard configurations',
		'type' 				=> 'read',
		'loginrequired' 	=> true,
		'ajax' 				=> true,
	],
	'format_serial3_get_quizzes' => [
		'classname' 		=> 'format_serial3_activities_external',
		'methodname' 		=> 'get_quizzes',
		'classpath' 		=> 'course/format/serial3/ws/activities.php',
		'description' 		=> 'Get student quiz data',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_assignments' => [
		'classname' 		=> 'format_serial3_activities_external',
		'methodname' 		=> 'get_assignments',
		'classpath' 		=> 'course/format/serial3/ws/activities.php',
		'description' 		=> 'Get student assignment data',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_user_understanding' => [
		'classname' 		=> 'format_serial3_learner_external',
		'methodname' 		=> 'get_user_understanding',
		'classpath' 		=> 'course/format/serial3/ws/learner.php',
		'description' 		=> 'Set the completion status of activities',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_set_user_understanding' => [
		'classname' 		=> 'format_serial3_learner_external',
		'methodname' 		=> 'set_user_understanding',
		'classpath' 		=> 'course/format/serial3/ws/learner.php',
		'description' 		=> 'Set the completion status of activities',
		'type' 				=> 'write',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_deadlines' => [
		'classname' 		=> 'format_serial3_activities_external',
		'methodname' 		=> 'get_deadlines',
		'classpath' 		=> 'course/format/serial3/ws/activities.php',
		'description' 		=> 'Get due dates of assignments and quizzes',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_set_learner_goal' => [
		'classname' 		=> 'format_serial3_learner_external',
		'methodname' 		=> 'set_learner_goal',
		'classpath' 		=> 'course/format/serial3/ws/learner.php',
		'description' 		=> 'Set the users learner goal',
		'type' 				=> 'write',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_learner_goal' => [
		'classname' 		=> 'format_serial3_learner_external',
		'methodname' 		=> 'get_learner_goal',
		'classpath' 		=> 'course/format/serial3/ws/learner.php',
		'description' 		=> 'Get the users learner goal.',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_missed_activities' => [
		'classname' 		=> 'format_serial3_activities_external',
		'methodname' 		=> 'get_missed_activities',
		'classpath' 		=> 'course/format/serial3/ws/activities.php',
		'description' 		=> 'Get the users learner goal.',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_forum_posts' => [
		'classname' 		=> 'format_serial3_forum_external',
		'methodname' 		=> 'get_forum_posts',
		'classpath' 		=> 'course/format/serial3/ws/forum.php',
		'description' 		=> 'Get the users learner goal.',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_set_rule_response' => [
		'classname' 		=> 'format_serial3_misc_external',
		'methodname' 		=> 'set_rule_response',
		'classpath' 		=> 'course/format/serial3/ws/misc.php',
		'description' 		=> 'Get the users learner goal.',
		'type' 				=> 'write',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_recommendations' => [
		'classname' 		=> 'format_serial3_misc_external',
		'methodname' 		=> 'get_recommendations',
		'classpath' 		=> 'course/format/serial3/ws/misc.php',
		'description' 		=> 'Get recommendations for the user',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_logger' => [
		'classname' 		=> 'format_serial3_misc_external',
		'methodname' 		=> 'logger',
		'classpath' 		=> 'course/format/serial3/ws/misc.php',
		'description' 		=> 'Collect log data from the client',
		'type' 				=> 'write',
		'ajax' 				=> true,
		'capabilities'  	=> 'format/serial3:view',
	],
	'format_serial3_get_all_teachers_of_course' => [
		'classname' 		=> 'format_serial3_teacher_external',
		'methodname' 		=> 'get_all_teachers_of_course',
		'classpath' 		=> 'course/format/serial3/ws/teacher.php',
		'description' 		=> 'Get all teachers of a course',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_last_access_of_teachers_of_course' => [
		'classname' 		=> 'format_serial3_teacher_external',
		'methodname' 		=> 'get_last_access_of_teachers_of_course',
		'classpath' 		=> 'course/format/serial3/ws/teacher.php',
		'description' 		=> 'Get last access times of teachers in a course',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_added_or_changed_course_resources' => [
		'classname' 		=> 'format_serial3_course_external',
		'methodname' 		=> 'get_added_or_changed_course_resources',
		'classpath' 		=> 'course/format/serial3/ws/course.php',
		'description' 		=> 'Get added or changed course resources',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_deleted_course_resources' => [
		'classname' 		=> 'format_serial3_course_external',
		'methodname' 		=> 'get_deleted_course_resources',
		'classpath' 		=> 'course/format/serial3/ws/course.php',
		'description' 		=> 'Get deleted course resources',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
	'format_serial3_get_new_forum_discussions' => [
		'classname' 		=> 'format_serial3_forum_external',
		'methodname' 		=> 'get_new_forum_discussions',
		'classpath' 		=> 'course/format/serial3/ws/forum.php',
		'description' 		=> 'Get new forum discussions by teachers',
		'type' 				=> 'read',
		'ajax' 				=> true,
		'loginrequired' 	=> true
	],
];