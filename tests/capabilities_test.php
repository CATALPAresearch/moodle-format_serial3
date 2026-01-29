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
 * Tests for capabilities defined in db/access.php.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

/**
 * Test cases for capabilities.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capabilities_test extends \advanced_testcase {

    /**
     * Test access.php file exists.
     */
    public function test_access_file_exists() {
        global $CFG;
        
        $accessFile = $CFG->dirroot . '/course/format/serial3/db/access.php';
        $this->assertFileExists($accessFile);
    }

    /**
     * Test capabilities are properly defined.
     */
    public function test_capabilities_defined() {
        global $CFG;
        
        $capabilities = [];
        require($CFG->dirroot . '/course/format/serial3/db/access.php');
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
    }

    /**
     * Test specific capabilities exist.
     */
    public function test_specific_capabilities_exist() {
        global $CFG;
        
        $capabilities = [];
        require($CFG->dirroot . '/course/format/serial3/db/access.php');
        
        $expectedCapabilities = [
            'format/serial3:view',
        ];
        
        foreach ($expectedCapabilities as $capability) {
            $this->assertArrayHasKey($capability, $capabilities, "Capability {$capability} should be defined");
        }
    }

    /**
     * Test capability structure is valid.
     */
    public function test_capability_structure() {
        global $CFG;
        
        $capabilities = [];
        require($CFG->dirroot . '/course/format/serial3/db/access.php');
        
        foreach ($capabilities as $capname => $capability) {
            $this->assertArrayHasKey('captype', $capability, "Capability {$capname} should have captype");
            $this->assertArrayHasKey('contextlevel', $capability, "Capability {$capname} should have contextlevel");
            $this->assertArrayHasKey('archetypes', $capability, "Capability {$capname} should have archetypes");
            $this->assertIsArray($capability['archetypes'], "Capability {$capname} archetypes should be array");
        }
    }

    /**
     * Test capabilities are registered in the system.
     */
    public function test_capabilities_registered() {
        $this->resetAfterTest(true);
        
        $capabilities = get_all_capabilities();
        $serial3caps = array_filter($capabilities, function($cap) {
            return strpos($cap['name'], 'format/serial3:') === 0;
        });
        
        $this->assertNotEmpty($serial3caps, 'At least one format_serial3 capability should be registered');
    }
}
