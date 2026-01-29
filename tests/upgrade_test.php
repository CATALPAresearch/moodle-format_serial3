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
 * Tests for upgrade scripts.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

/**
 * Test cases for upgrade functionality.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade_test extends \advanced_testcase {

    /**
     * Test upgrade.php file exists.
     */
    public function test_upgrade_file_exists() {
        global $CFG;
        
        $upgradeFile = $CFG->dirroot . '/course/format/serial3/db/upgrade.php';
        $this->assertFileExists($upgradeFile);
    }

    /**
     * Test upgrade function is defined.
     */
    public function test_upgrade_function_defined() {
        global $CFG;
        
        require_once($CFG->dirroot . '/course/format/serial3/db/upgrade.php');
        
        $this->assertTrue(
            function_exists('xmldb_format_serial3_upgrade'),
            'xmldb_format_serial3_upgrade function should be defined'
        );
    }

    /**
     * Test upgrade function returns boolean.
     */
    public function test_upgrade_function_returns_bool() {
        global $CFG;
        $this->resetAfterTest(true);
        
        require_once($CFG->dirroot . '/course/format/serial3/db/upgrade.php');
        
        $result = xmldb_format_serial3_upgrade(0);
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test upgradelib file exists.
     */
    public function test_upgradelib_file_exists() {
        global $CFG;
        
        $upgradelibFile = $CFG->dirroot . '/course/format/serial3/db/upgradelib.php';
        $this->assertFileExists($upgradelibFile);
    }

    /**
     * Test upgrade.php includes upgradelib.
     */
    public function test_upgrade_includes_upgradelib() {
        global $CFG;
        
        $upgradeContent = file_get_contents($CFG->dirroot . '/course/format/serial3/db/upgrade.php');
        $this->assertStringContainsString('upgradelib.php', $upgradeContent);
    }
}
