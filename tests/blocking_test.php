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
 * Tests for blocking class.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

/**
 * Test cases for blocking functionality.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_serial3\blocking
 */
class blocking_test extends \advanced_testcase {

    /**
     * Test blocking class exists.
     */
    public function test_blocking_class_exists() {
        $this->assertTrue(class_exists('\format_serial3\blocking'));
    }

    /**
     * Test blocking class constants are defined.
     */
    public function test_blocking_constants() {
        $reflection = new \ReflectionClass('\format_serial3\blocking');
        
        $this->assertTrue($reflection->hasConstant('POLICY_VERSION'));
        $this->assertTrue($reflection->hasConstant('DISABLE_BLOCKING'));
        $this->assertTrue($reflection->hasConstant('DISABLE_WHITELIST'));
        $this->assertTrue($reflection->hasConstant('WHITELIST'));
        
        $this->assertIsInt(blocking::POLICY_VERSION);
        $this->assertIsBool(blocking::DISABLE_BLOCKING);
        $this->assertIsBool(blocking::DISABLE_WHITELIST);
        $this->assertIsArray(blocking::WHITELIST);
    }

    /**
     * Test tool_policy_accepted method exists.
     */
    public function test_tool_policy_accepted_method_exists() {
        $reflection = new \ReflectionClass('\format_serial3\blocking');
        $this->assertTrue($reflection->hasMethod('tool_policy_accepted'));
        
        $method = $reflection->getMethod('tool_policy_accepted');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test whitelist contains localhost entries.
     */
    public function test_whitelist_contains_localhost() {
        $whitelist = blocking::WHITELIST;
        
        $this->assertContains('127.0.0.1', $whitelist);
        $this->assertContains('::1', $whitelist);
        $this->assertContains('localhost', $whitelist);
    }

    /**
     * Test policy version is positive integer.
     */
    public function test_policy_version_valid() {
        $this->assertGreaterThan(0, blocking::POLICY_VERSION);
    }
}
