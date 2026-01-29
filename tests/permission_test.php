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
 * Tests for permission classes.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3\permission;

/**
 * Test cases for permission functionality.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_serial3\permission\context
 * @covers \format_serial3\permission\course
 * @covers \format_serial3\permission\coursecategory
 * @covers \format_serial3\permission\module
 */
class permission_test extends \advanced_testcase {

    /**
     * Test permission context class exists.
     */
    public function test_permission_context_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\permission\context'));
    }

    /**
     * Test permission course class exists.
     */
    public function test_permission_course_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\permission\course'));
    }

    /**
     * Test permission coursecategory class exists.
     */
    public function test_permission_coursecategory_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\permission\coursecategory'));
    }

    /**
     * Test permission module class exists.
     */
    public function test_permission_module_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\permission\module'));
    }

    /**
     * Test permission course extends context.
     */
    public function test_permission_course_extends_context() {
        $reflection = new \ReflectionClass('\format_serial3\permission\course');
        $this->assertEquals(
            'format_serial3\permission\context',
            $reflection->getParentClass()->getName()
        );
    }

    /**
     * Test permission coursecategory extends context.
     */
    public function test_permission_coursecategory_extends_context() {
        $reflection = new \ReflectionClass('\format_serial3\permission\coursecategory');
        $this->assertEquals(
            'format_serial3\permission\context',
            $reflection->getParentClass()->getName()
        );
    }

    /**
     * Test permission module extends context.
     */
    public function test_permission_module_extends_context() {
        $reflection = new \ReflectionClass('\format_serial3\permission\module');
        $this->assertEquals(
            'format_serial3\permission\context',
            $reflection->getParentClass()->getName()
        );
    }

    /**
     * Test context class has required methods.
     */
    public function test_context_has_required_methods() {
        $reflection = new \ReflectionClass('\format_serial3\permission\context');
        
        $requiredMethods = [
            'is_site_admin',
            'is_manager',
            '__construct',
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Context class should have method {$method}"
            );
        }
    }

    /**
     * Test permission course can be instantiated.
     */
    public function test_permission_course_instantiation() {
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        
        $this->setUser($user);
        
        $permission = new course($user->id, $course->id);
        $this->assertInstanceOf('\format_serial3\permission\course', $permission);
    }

    /**
     * Test permission coursecategory can be instantiated.
     */
    public function test_permission_coursecategory_instantiation() {
        $this->resetAfterTest(true);
        
        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();
        
        $this->setUser($user);
        
        $permission = new coursecategory($user->id, $category->id);
        $this->assertInstanceOf('\format_serial3\permission\coursecategory', $permission);
    }
}
