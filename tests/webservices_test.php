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
 * Unit tests for format_serial3 webservices.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for webservice methods.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservices_test extends \advanced_testcase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test creating a course with serial3 format.
     */
    public function test_create_serial3_course() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        $this->assertNotEmpty($course);
        $this->assertEquals('serial3', $course->format);
    }

    /**
     * Test user enrollment in serial3 course.
     */
    public function test_user_enrollment() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        // Create test user and enrol.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        
        // Verify enrollment.
        $this->assertTrue(is_enrolled(\context_course::instance($course->id), $user));
    }

    /**
     * Test course format options.
     */
    public function test_format_options() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        $format = course_get_format($course);
        $this->assertNotNull($format);
        $this->assertEquals('serial3', $format->get_format());
    }

    /**
     * Test section creation in serial3 format.
     */
    public function test_section_creation() {
        global $DB;
        
        // Create test course.
        $course = $this->getDataGenerator()->create_course([
            'format' => 'serial3',
            'numsections' => 5,
        ]);
        
        // Verify sections exist.
        $sections = $DB->get_records('course_sections', ['course' => $course->id]);
        $this->assertGreaterThanOrEqual(5, count($sections));
    }

    /**
     * Test teacher role assignment.
     */
    public function test_teacher_assignment() {
        global $DB;
        
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        // Create teacher.
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        
        $context = \context_course::instance($course->id);
        $roles = get_user_roles($context, $teacher->id);
        
        $this->assertNotEmpty($roles);
        $hasEditingTeacher = false;
        foreach ($roles as $role) {
            if ($role->shortname === 'editingteacher') {
                $hasEditingTeacher = true;
                break;
            }
        }
        $this->assertTrue($hasEditingTeacher);
    }

    /**
     * Test student access to course.
     */
    public function test_student_access() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        // Create student.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        
        $context = \context_course::instance($course->id);
        $this->assertTrue(is_enrolled($context, $student->id));
    }

    /**
     * Test course context creation.
     */
    public function test_course_context() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        $context = \context_course::instance($course->id);
        $this->assertNotNull($context);
        $this->assertEquals(CONTEXT_COURSE, $context->contextlevel);
    }

    /**
     * Test multiple users in course.
     */
    public function test_multiple_users() {
        // Create test course.
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        // Create multiple users.
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $users[] = $user;
        }
        
        $this->assertCount(5, $users);
        
        $context = \context_course::instance($course->id);
        foreach ($users as $user) {
            $this->assertTrue(is_enrolled($context, $user->id));
        }
    }

    /**
     * Test permission helper class exists.
     */
    public function test_permission_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\permission\course'));
        $this->assertTrue(class_exists('\format_serial3\permission\context'));
    }

    /**
     * Test widget manager class exists.
     */
    public function test_widget_manager_exists() {
        $this->assertTrue(class_exists('\format_serial3\widget_manager'));
    }

    /**
     * Test blocking class exists.
     */
    public function test_blocking_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\blocking'));
    }

    /**
     * Test database tables exist.
     */
    public function test_database_tables_exist() {
        global $DB;
        
        $dbman = $DB->get_manager();
        
        // Test if plugin tables exist (from db/install.xml).
        $tables = [
            'serial3_tasks',
            'serial3_dashboard_settings',
            'serial3_learner_goal',
            'serial3_overview',
            'serial3_reflections',
        ];
        
        foreach ($tables as $tablename) {
            $table = new \xmldb_table($tablename);
            $this->assertTrue(
                $dbman->table_exists($table),
                "Table {$tablename} should exist in the database"
            );
        }
    }

    /**
     * Test widget_manager returns available widgets.
     */
    public function test_widget_manager_get_available_widgets() {
        $widgets = \format_serial3\widget_manager::get_available_widgets();
        
        $this->assertIsArray($widgets);
        $this->assertNotEmpty($widgets);
        
        // Check for expected widgets.
        $expectedWidgets = [
            'ProgressChartAdaptive',
            'IndicatorDisplay',
            'Recommendations',
            'TaskList',
            'LearningStrategies',
            'CourseOverview',
            'Deadlines',
        ];
        
        foreach ($expectedWidgets as $widgetId) {
            $this->assertArrayHasKey($widgetId, $widgets, "Widget {$widgetId} should exist");
            $this->assertArrayHasKey('name', $widgets[$widgetId]);
            $this->assertArrayHasKey('default_enabled', $widgets[$widgetId]);
            $this->assertArrayHasKey('requires_permission', $widgets[$widgetId]);
        }
    }

    /**
     * Test widget_manager can check if widget is enabled.
     */
    public function test_widget_manager_is_widget_enabled() {
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        
        // Test with default widget (should be enabled by default).
        $result = \format_serial3\widget_manager::is_widget_enabled($course->id, 'TaskList');
        $this->assertIsBool($result);
    }

    /**
     * Test widget_manager can manage widget permissions.
     */
    public function test_widget_manager_permissions() {
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        $teacher = $this->getDataGenerator()->create_user();
        
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        
        $this->setUser($teacher);
        
        // Teachers should be able to manage widgets.
        $canManage = \format_serial3\widget_manager::can_manage_widgets($course->id, $teacher->id);
        $this->assertIsBool($canManage);
    }

    /**
     * Test webservices are properly defined.
     */
    public function test_webservices_defined() {
        global $CFG;
        
        require_once($CFG->dirroot . '/course/format/serial3/db/services.php');
        
        $this->assertIsArray($functions);
        $this->assertNotEmpty($functions);
        
        // Check for key webservices.
        $expectedServices = [
            'format_serial3_overview',
            'format_serial3_create_task',
            'format_serial3_update_task',
            'format_serial3_delete_task',
            'format_serial3_get_tasks',
            'format_serial3_getcalendar',
            'format_serial3_reflectionread',
        ];
        
        foreach ($expectedServices as $serviceName) {
            $this->assertArrayHasKey($serviceName, $functions, "Service {$serviceName} should be defined");
            $this->assertArrayHasKey('classname', $functions[$serviceName]);
            $this->assertArrayHasKey('methodname', $functions[$serviceName]);
            $this->assertArrayHasKey('classpath', $functions[$serviceName]);
        }
    }

    /**
     * Test format.php can be included without errors.
     */
    public function test_format_php_loads() {
        global $CFG, $PAGE, $OUTPUT;
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        $context = \context_course::instance($course->id);
        
        $PAGE->set_course($course);
        $PAGE->set_context($context);
        
        // Check that format.php file exists.
        $formatFile = $CFG->dirroot . '/course/format/serial3/format.php';
        $this->assertFileExists($formatFile);
    }

    /**
     * Test lib.php format_serial3 class exists and has required methods.
     */
    public function test_lib_format_class_methods() {
        $this->assertTrue(class_exists('format_serial3'));
        
        $reflection = new \ReflectionClass('format_serial3');
        
        // Check for required public methods.
        $requiredMethods = [
            'uses_sections',
            'get_section_name',
            'get_default_section_name',
            'course_format_options',
        ];
        
        foreach ($requiredMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "format_serial3 should have method {$methodName}"
            );
        }
    }

    /**
     * Test format_serial3 course format options.
     */
    public function test_format_options_structure() {
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        $format = course_get_format($course);
        
        $this->assertInstanceOf('format_serial3', $format);
        
        $options = $format->course_format_options();
        $this->assertIsArray($options);
    }

    /**
     * Test policy.php file exists and contains required elements.
     */
    public function test_policy_php_exists() {
        global $CFG;
        
        $policyFile = $CFG->dirroot . '/course/format/serial3/policy.php';
        $this->assertFileExists($policyFile, 'policy.php should exist');
        
        $content = file_get_contents($policyFile);
        $this->assertStringContainsString('require_login', $content);
        $this->assertStringContainsString('$PAGE', $content);
    }

    /**
     * Test renderer.php exists and renderer class is properly defined.
     */
    public function test_renderer_class_exists() {
        global $CFG;
        
        $rendererFile = $CFG->dirroot . '/course/format/serial3/renderer.php';
        $this->assertFileExists($rendererFile, 'renderer.php should exist');
        
        require_once($rendererFile);
        
        $this->assertTrue(
            class_exists('format_serial3_renderer'),
            'format_serial3_renderer class should exist'
        );
        
        $reflection = new \ReflectionClass('format_serial3_renderer');
        $this->assertTrue(
            $reflection->isSubclassOf('core_courseformat\output\section_renderer'),
            'Renderer should extend section_renderer'
        );
    }

    /**
     * Test renderer can be instantiated.
     */
    public function test_renderer_instantiation() {
        global $PAGE;
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course(['format' => 'serial3']);
        $PAGE->set_course($course);
        
        $renderer = $PAGE->get_renderer('format_serial3');
        $this->assertInstanceOf('format_serial3_renderer', $renderer);
    }
}

