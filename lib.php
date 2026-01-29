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
 * This file contains main class for the course format Serial3
 *
 * @since     Moodle 2.0
 * @package   format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');

use core_courseformat\base as format_base;
use core\output\inplace_editable;
use context_course;
use moodle_url;
use navigation_node;
use stdClass;
use lang_string;
use MoodleQuickForm;
use cm_info;
use section_info;
use global_navigation;

/**
 * Main class for the Serial3 course format
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_serial3 extends format_base {

    /** @var bool Survey enabled flag */
    private $SURVEY_ENABLED = true;

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section): string {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                    array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section): string {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_serial3');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()): ?moodle_url {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax(): stdClass {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node): void {    

        // SURVEY START
        
        global $COURSE, $DB, $CFG, $USER, $PAGE;

        $perm = new \format_serial3\permission\course($USER->id, $COURSE->id);

        if ($this->SURVEY_ENABLED === true && !$perm->isAnyKindOfModerator()) {             
            $records = $DB->get_records_sql(
                'SELECT * FROM {limesurvey_assigns} WHERE course_id = ?', 
                array($COURSE->id)
            );                   
            foreach ($records as $record) {                
                $submissionexists = $DB->record_exists_sql(
                    'SELECT * FROM {limesurvey_submissions} WHERE user_id = ? AND survey_id = ?', 
                    array($USER->id, $record->survey_id)
                );
                if ($submissionexists === false) {
                    if (isset($record->startdate) && !is_null($record->startdate) && is_int(+$record->startdate)) {
                        if ($record->startdate > time()) {
                            continue;
                        }
                    }
                    if (isset($record->stopdate) && !is_null($record->stopdate) && is_int(+$record->stopdate)) {
                        if ($record->stopdate < time()) {
                            continue;
                        }
                    }
                    if (isset($record->warndate) && !is_null($record->warndate) && is_int(+$record->warndate)) {
                        if ($record->warndate > time()) {
                            continue;
                        }
                    }
                    $redirectToSurvey = new moodle_url('/course/format/serial3/survey.php', array('c' => $COURSE->id));
                    redirect($redirectToSurvey);
                }
            }
        }      
      
        // SURVEY END
        
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move(): array {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks(): array {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Serial3 format uses the following options:
     * - coursedisplay
     * - hiddensections
     * - dashboardsectionexclude
     * - sectioncollapsenabled
     * - sectioninitiallycollapsed
     * - enabled_widgets (JSON array of enabled widget IDs)
     * - widget_settings_* (JSON settings for each widget)
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false): array {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ),
                'dashboardsectionexclude' => array(
                    'default' => '',
                    'type' => PARAM_TEXT,
                ),
                'sectioncollapsenabled' => array(
                    'default' => property_exists($courseconfig, "sectioncollapsenabled") ? 
                        $courseconfig->sectioncollapsenabled : 0,
                    'type' => PARAM_INT,
                ),
                'sectioninitiallycollapsed' => array(
                    'default' => property_exists($courseconfig, "sectioninitiallycollapsed") ? 
                        $courseconfig->sectioninitiallycollapsed : 0,
                    'type' => PARAM_INT,
                ),
                'enabled_widgets' => array(
                    'default' => '',
                    'type' => PARAM_TEXT,
                ),
            );
        }
        if ($foreditform) {
            $courseformatoptionsedit = array(
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay', 'format_serial3'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ),
                'dashboardsectionexclude' => array(
                    'label' =>  get_string('dashboardsectionexclude', 'format_serial3'),
                    'element_type' => 'text'
                ),
                'sectioncollapsenabled' => array(
                    'label' =>  get_string('sectioncollapsenabled', 'format_serial3'),
                    'element_type' => 'advcheckbox',
                    'help' => 'sectioncollapsenabled',
                    'help_component' => 'moodle',
                ),
                'sectioninitiallycollapsed' => array( 
                    'label' =>  get_string('sectioninitiallycollapsed', 'format_serial3'),
                    'element_type' => 'advcheckbox',
                    'help' => 'sectioninitiallycollapsed',
                    'help_component' => 'moodle',
                ),
            );
            
            // Add widget configuration to edit form
            $this->add_widget_settings_to_form($courseformatoptionsedit);
            
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }
    
    /**
     * Add widget settings to course format options
     *
     * @param array &$options Reference to options array
     */
    private function add_widget_settings_to_form(array &$options): void {
        require_once(__DIR__ . '/classes/widget_manager.php');
        $widgets = \format_serial3\widget_manager::get_available_widgets();
        
        // Add header for widget settings
        $options['widgets_header'] = array(
            'label' => get_string('widgets_header', 'format_serial3'),
            'element_type' => 'header',
        );
        
        // Add enabled widgets multiselect
        $widgetoptions = array();
        foreach ($widgets as $widgetid => $widget) {
            $widgetoptions[$widgetid] = $widget['name'];
        }
        
        $options['enabled_widgets'] = array(
            'label' => get_string('enabled_widgets', 'format_serial3'),
            'help' => 'enabled_widgets',
            'help_component' => 'format_serial3',
            'element_type' => 'autocomplete',
            'element_attributes' => array(
                $widgetoptions,
                array('multiple' => true)
            ),
        );
        
        // Add link to dedicated settings page instead of adding all fields here
        $courseid = $this->get_course()->id;
        if ($courseid > 0) {
            $settingsurl = new \moodle_url('/course/format/serial3/widgets.php', array('id' => $courseid));
            $options['widgets_settings_link'] = array(
                'label' => get_string('widget_settings_advanced', 'format_serial3'),
                'element_type' => 'static',
                'element_attributes' => array(
                    \html_writer::link($settingsurl, get_string('widget_settings_advanced_link', 'format_serial3'),
                        array('target' => '_blank', 'class' => 'btn btn-secondary'))
                ),
            );
        }
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false): array {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'serial3', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null): bool {
        $data = (array)$data;
        
        // Filter out widget settings - these are handled separately via widget_manager
        $filtereddata = [];
        foreach ($data as $key => $value) {
            // Only include keys that don't start with 'widget_settings_'
            if (strpos($key, 'widget_settings_') !== 0) {
                $filtereddata[$key] = $value;
            }
        }
        $data = $filtereddata;
        
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'sectioncollapsenabled') {
                        $data['sectioncollapsenabled'] = 0;
                    } else if ($key === 'sectioninitiallycollapsed') {
                        $data['sectioninitiallycollapsed'] = 0;
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section): bool {
        return true;
    }

    /**
     * Prepares the templateable object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return \core\output\inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, 
                                                         $editlabel = null): inplace_editable {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_serial3');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_serial3', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news(): bool {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section): bool {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Perform custom action on section
     *
     * @param stdClass|section_info $section
     * @param string $action
     * @param int $sr
     * @return null|array
     */
    public function section_action($section, $action, $sr): ?array {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'serial3' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_serial3');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_serial3_inplace_editable($itemtype, $itemid, $newvalue): inplace_editable {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'serial3'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}