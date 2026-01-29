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
 * Privacy provider tests for format_serial3.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Privacy provider tests.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_serial3\privacy\provider
 */
class privacy_test extends provider_testcase {

    /**
     * Test privacy provider class exists.
     */
    public function test_privacy_provider_exists() {
        $this->assertTrue(class_exists('\format_serial3\privacy\provider'));
    }

    /**
     * Test provider implements required interfaces.
     */
    public function test_provider_implements_interfaces() {
        $reflection = new \ReflectionClass('\format_serial3\privacy\provider');
        
        $interfaces = $reflection->getInterfaceNames();
        
        $this->assertContains('core_privacy\local\metadata\provider', $interfaces);
        $this->assertContains('core_privacy\local\request\plugin\provider', $interfaces);
        $this->assertContains('core_privacy\local\request\core_userlist_provider', $interfaces);
    }

    /**
     * Test get_metadata returns collection.
     */
    public function test_get_metadata_returns_collection() {
        $collection = new collection('format_serial3');
        $result = provider::get_metadata($collection);
        
        $this->assertInstanceOf(collection::class, $result);
    }

    /**
     * Test metadata includes required tables.
     */
    public function test_metadata_includes_tables() {
        $collection = new collection('format_serial3');
        $metadata = provider::get_metadata($collection);
        
        $items = $metadata->get_collection();
        $tables = [];
        
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tables[] = $item->get_name();
            }
        }
        
        $expectedTables = [
            'serial3_tasks',
            'serial3_overview',
            'serial3_learner_goal',
        ];
        
        foreach ($expectedTables as $table) {
            $this->assertContains($table, $tables, "Metadata should include table {$table}");
        }
    }

    /**
     * Test provider has required methods.
     */
    public function test_provider_has_required_methods() {
        $reflection = new \ReflectionClass('\format_serial3\privacy\provider');
        
        $requiredMethods = [
            'get_metadata',
            'get_contexts_for_userid',
            'export_user_data',
            'delete_data_for_all_users_in_context',
            'delete_data_for_user',
            'get_users_in_context',
            'delete_data_for_users',
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Provider should have method {$method}"
            );
        }
    }
}
