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
 * Widget settings page for format_serial3
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/classes/widget_manager.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

// Check if course format is serial3
if ($course->format !== 'serial3') {
    throw new moodle_exception('invalidcourseformat', 'error');
}

$PAGE->set_url('/course/format/serial3/widgets.php', array('id' => $courseid));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title(get_string('widget_settings', 'format_serial3', format_string($course->fullname)));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('widget_settings', 'format_serial3'));

// Handle form submission
if (optional_param('save', false, PARAM_BOOL) && confirm_sesskey()) {
    // Get enabled widgets
    $enabled = optional_param_array('enabled_widgets', array(), PARAM_ALPHANUMEXT);
    
    // Save enabled widgets
    \format_serial3\widget_manager::save_enabled_widgets($courseid, $enabled);
    
    // Save widget-specific settings
    $widgets = \format_serial3\widget_manager::get_available_widgets();
    foreach ($widgets as $widgetid => $widget) {
        if (!empty($widget['settings'])) {
            $settings = array();
            foreach ($widget['settings'] as $settingkey => $setting) {
                $paramname = 'widget_' . $widgetid . '_' . $settingkey;
                if ($setting['type'] === 'checkbox') {
                    $settings[$settingkey] = optional_param($paramname, 0, PARAM_INT);
                } else {
                    $settings[$settingkey] = optional_param($paramname, '', PARAM_RAW);
                }
            }
            \format_serial3\widget_manager::save_widget_settings($courseid, $widgetid, $settings);
        }
    }
    
    redirect(new moodle_url('/course/format/serial3/widgets.php', array('id' => $courseid)),
             get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get current settings
$widgets = \format_serial3\widget_manager::get_available_widgets();
$enabledwidgets = \format_serial3\widget_manager::get_enabled_widgets($courseid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('widget_settings', 'format_serial3'));

// Display form
echo html_writer::start_tag('form', array(
    'method' => 'post',
    'action' => new moodle_url('/course/format/serial3/widgets.php', array('id' => $courseid))
));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

echo html_writer::start_tag('div', array('class' => 'widget-settings'));

// Widget selection
echo $OUTPUT->heading(get_string('enabled_widgets', 'format_serial3'), 3);
echo html_writer::tag('p', get_string('enabled_widgets_help', 'format_serial3'), array('class' => 'text-muted'));

foreach ($widgets as $widgetid => $widget) {
    $checked = in_array($widgetid, $enabledwidgets);
    
    echo html_writer::start_div('form-check mb-3');
    echo html_writer::checkbox('enabled_widgets[]', $widgetid, $checked, '', array(
        'id' => 'widget_' . $widgetid,
        'class' => 'form-check-input'
    ));
    echo html_writer::label($widget['name'], 'widget_' . $widgetid, true, array('class' => 'form-check-label'));
    echo html_writer::tag('div', $widget['description'], array('class' => 'form-text text-muted small'));
    echo html_writer::end_div();
}

// Widget-specific settings
foreach ($widgets as $widgetid => $widget) {
    if (!empty($widget['settings'])) {
        echo $OUTPUT->heading($widget['name'] . ' - ' . get_string('settings'), 3, 'mt-4');
        
        $widgetsettings = \format_serial3\widget_manager::get_widget_settings($courseid, $widgetid);
        
        foreach ($widget['settings'] as $settingkey => $setting) {
            $paramname = 'widget_' . $widgetid . '_' . $settingkey;
            $currentvalue = $widgetsettings[$settingkey] ?? $setting['default'];
            
            echo html_writer::start_div('form-group mb-3');
            
            if ($setting['type'] === 'checkbox') {
                echo html_writer::checkbox($paramname, 1, $currentvalue, $setting['label'], array(
                    'id' => $paramname,
                    'class' => 'form-check-input'
                ));
            } else {
                echo html_writer::label($setting['label'], $paramname, true, array('class' => 'form-label'));
                echo html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => $paramname,
                    'id' => $paramname,
                    'value' => $currentvalue,
                    'class' => 'form-control'
                ));
            }
            
            echo html_writer::end_div();
        }
    }
}

echo html_writer::end_tag('div');

// Submit button
echo html_writer::start_div('mt-4');
echo html_writer::empty_tag('input', array(
    'type' => 'submit',
    'name' => 'save',
    'value' => get_string('savechanges'),
    'class' => 'btn btn-primary'
));
echo html_writer::link(new moodle_url('/course/view.php', array('id' => $courseid)),
                       get_string('cancel'),
                       array('class' => 'btn btn-secondary ml-2'));
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
