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
 * Tests for webservice definitions in db/services.php.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

/**
 * Test cases for webservice definitions.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class services_test extends \advanced_testcase {

    /**
     * @var array List of service definitions
     */
    private $functions = [];

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        
        require($CFG->dirroot . '/course/format/serial3/db/services.php');
        $this->functions = $functions;
    }

    /**
     * Test services.php file exists.
     */
    public function test_services_file_exists() {
        global $CFG;
        
        $servicesFile = $CFG->dirroot . '/course/format/serial3/db/services.php';
        $this->assertFileExists($servicesFile);
    }

    /**
     * Test services are properly defined.
     */
    public function test_services_defined() {
        $this->assertIsArray($this->functions);
        $this->assertNotEmpty($this->functions);
    }

    /**
     * Test all service definitions have required keys.
     */
    public function test_services_have_required_keys() {
        foreach ($this->functions as $serviceName => $service) {
            $this->assertArrayHasKey('classname', $service, "Service {$serviceName} should have classname");
            $this->assertArrayHasKey('methodname', $service, "Service {$serviceName} should have methodname");
            $this->assertArrayHasKey('classpath', $service, "Service {$serviceName} should have classpath");
            $this->assertArrayHasKey('description', $service, "Service {$serviceName} should have description");
            $this->assertArrayHasKey('type', $service, "Service {$serviceName} should have type");
            $this->assertArrayHasKey('ajax', $service, "Service {$serviceName} should have ajax");
        }
    }

    /**
     * Test all service class files exist.
     */
    public function test_service_class_files_exist() {
        global $CFG;
        
        foreach ($this->functions as $serviceName => $service) {
            $classpath = $CFG->dirroot . '/' . $service['classpath'];
            $this->assertFileExists(
                $classpath,
                "Service {$serviceName} classpath should exist: {$service['classpath']}"
            );
        }
    }

    /**
     * Test service types are valid.
     */
    public function test_service_types_valid() {
        $validTypes = ['read', 'write'];
        
        foreach ($this->functions as $serviceName => $service) {
            $this->assertContains(
                $service['type'],
                $validTypes,
                "Service {$serviceName} type should be 'read' or 'write'"
            );
        }
    }

    /**
     * Test ajax flag is boolean.
     */
    public function test_ajax_flag_is_boolean() {
        foreach ($this->functions as $serviceName => $service) {
            $this->assertIsBool(
                $service['ajax'],
                "Service {$serviceName} ajax flag should be boolean"
            );
        }
    }

    /**
     * Test specific services exist.
     */
    public function test_specific_services_exist() {
        $expectedServices = [
            'format_serial3_overview',
            'format_serial3_create_task',
            'format_serial3_update_task',
            'format_serial3_delete_task',
            'format_serial3_get_tasks',
            'format_serial3_getcalendar',
            'format_serial3_reflectionread',
            'format_serial3_reflectioncreate',
            'format_serial3_get_surveys',
        ];
        
        foreach ($expectedServices as $serviceName) {
            $this->assertArrayHasKey(
                $serviceName,
                $this->functions,
                "Service {$serviceName} should be defined"
            );
        }
    }

    /**
     * Test service class names follow naming convention.
     */
    public function test_service_classnames_convention() {
        foreach ($this->functions as $serviceName => $service) {
            $this->assertStringStartsWith(
                'format_serial3_',
                $service['classname'],
                "Service {$serviceName} classname should start with format_serial3_"
            );
            $this->assertStringEndsWith(
                '_external',
                $service['classname'],
                "Service {$serviceName} classname should end with _external"
            );
        }
    }

    /**
     * Test descriptions are not empty.
     */
    public function test_descriptions_not_empty() {
        foreach ($this->functions as $serviceName => $service) {
            $this->assertNotEmpty(
                $service['description'],
                "Service {$serviceName} should have a description"
            );
        }
    }

    /**
     * Test task-related services are consistent.
     */
    public function test_task_services_consistency() {
        $taskServices = [
            'format_serial3_create_task',
            'format_serial3_update_task',
            'format_serial3_delete_task',
            'format_serial3_get_tasks',
        ];
        
        foreach ($taskServices as $serviceName) {
            $this->assertArrayHasKey($serviceName, $this->functions);
            $this->assertEquals(
                'format_serial3_tasks_external',
                $this->functions[$serviceName]['classname'],
                "Task service {$serviceName} should use tasks_external class"
            );
            $this->assertEquals(
                'course/format/serial3/ws/tasks.php',
                $this->functions[$serviceName]['classpath'],
                "Task service {$serviceName} should use tasks.php classpath"
            );
        }
    }

    /**
     * Test write operations should have login specified.
     */
    public function test_write_operations_require_login() {
        foreach ($this->functions as $serviceName => $service) {
            if ($service['type'] === 'write') {
                // loginrequired may be optional, but if specified should be true for write operations
                if (isset($service['loginrequired'])) {
                    $this->assertTrue(
                        $service['loginrequired'],
                        "Write service {$serviceName} should require login if loginrequired is specified"
                    );
                }
            }
        }
    }

    /**
     * Test service classpaths point to ws directory.
     */
    public function test_classpaths_in_ws_directory() {
        foreach ($this->functions as $serviceName => $service) {
            $this->assertStringContainsString(
                '/ws/',
                $service['classpath'],
                "Service {$serviceName} classpath should be in ws directory"
            );
        }
    }

    /**
     * Test all unique classpaths exist.
     */
    public function test_all_unique_classpaths_exist() {
        global $CFG;
        
        $classpaths = array_unique(array_column($this->functions, 'classpath'));
        
        foreach ($classpaths as $classpath) {
            $fullPath = $CFG->dirroot . '/' . $classpath;
            $this->assertFileExists(
                $fullPath,
                "Classpath should exist: {$classpath}"
            );
        }
    }

    /**
     * Test service counts per class file.
     */
    public function test_service_distribution() {
        $servicesByClass = [];
        
        foreach ($this->functions as $serviceName => $service) {
            $classname = $service['classname'];
            if (!isset($servicesByClass[$classname])) {
                $servicesByClass[$classname] = [];
            }
            $servicesByClass[$classname][] = $serviceName;
        }
        
        // Verify we have services for key classes.
        $this->assertArrayHasKey('format_serial3_tasks_external', $servicesByClass);
        $this->assertArrayHasKey('format_serial3_misc_external', $servicesByClass);
        $this->assertArrayHasKey('format_serial3_activities_external', $servicesByClass);
        
        // Verify tasks class has expected number of services.
        $this->assertCount(
            4,
            $servicesByClass['format_serial3_tasks_external'],
            'Tasks external should have 4 services (create, update, delete, get)'
        );
    }
}
