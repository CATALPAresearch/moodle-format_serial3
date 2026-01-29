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
 * Upgrade scripts for course format "Serial3"
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/serial3/db/upgradelib.php');

/**
 * Upgrade script for format_serial3
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_format_serial3_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Add upgrade steps here as needed.
    // Each upgrade step should check the version and perform necessary database changes.
    //
    // Example:
    // if ($oldversion < 2026012301) {
    // Perform upgrade actions.
    // upgrade_plugin_savepoint(true, 2026012301, 'format', 'serial3');
    // }

    return true;
}
