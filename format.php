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
 * Serial3 course format display logic
 *
 * @package    format_serial3
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use format_serial3\blocking;
use core\context\course as context_course;
use core_courseformat\output\local\content as content_output;

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Backwards compatible parameter aliasing.
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}

// Get course context.
$context = context_course::instance($course->id);



// Retrieve course format option fields and add them to the $course object.
$courseformat = course_get_format($course);
$course = $courseformat->get_course();

// Handle section marker.
if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

/**
 * Check if the current user is a moderator for the course
 *
 * @return bool True if user is moderator, false otherwise
 */
function format_serial3_check_moderator_status(): bool {
    global $USER, $COURSE;
    
    try {
        // Site admins are always moderators.
        if (is_siteadmin($USER->id)) {
            return true;
        }
        
        // Must be logged in.
        if (!isloggedin()) {
            return false;
        }
        
        $context = context_course::instance($COURSE->id);
        $roles = get_user_roles($context, $USER->id);
        
        // Check for moderator-level roles.
        $moderatorroles = ['manager', 'coursecreator', 'teacher', 'editingteacher'];
        
        foreach ($roles as $role) {
            if (isset($role->shortname) && in_array($role->shortname, $moderatorroles)) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $ex) {
        debugging('Error checking moderator status: ' . $ex->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}


// Initialize JavaScript module with course data.
$PAGE->requires->js_call_amd('format_serial3/app-lazy', 'init', [
    'courseid' => $COURSE->id,
    'fullPluginName' => 'format_serial3',
    'userid' => $USER->id,
    'isModerator' => format_serial3_check_moderator_status(),
    'policyAccepted' => blocking::tool_policy_accepted(),
    'sectioncollapsenabled' => !empty($course->sectioncollapsenabled) ? (int)$course->sectioncollapsenabled : 0,
    'sectioninitiallycollapsed' => !empty($course->sectioninitiallycollapsed) ? (int)$course->sectioninitiallycollapsed : 0,
]);

// Output the Vue.js app container.
echo html_writer::start_tag('div', ['class' => 'format-serial3-container']);
echo html_writer::tag('div', '', ['id' => 'app']);
echo html_writer::end_tag('div');

// Get the course format renderer.
$renderer = $PAGE->get_renderer('format_serial3');

// Render the course content using the new method.
$outputclass = $courseformat->get_output_classname('content');
$widget = new $outputclass($courseformat);
echo $renderer->render($widget);

// Include course format JavaScript module (if still needed for legacy support).
$PAGE->requires->js('/course/format/serial3/format.js');