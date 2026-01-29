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
 * Blocking functionality class
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

defined('MOODLE_INTERNAL') || die();

/**
 * Policy version checking and user blocking management.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocking
{
    /** @var int The current policy version number */
    const POLICY_VERSION = 6; // Local_niels: 11 aple: 6, marc: 1.

    /** @var bool Whether to disable blocking functionality */
    const DISABLE_BLOCKING = false;

    /** @var bool Whether to disable whitelist checking */
    const DISABLE_WHITELIST = false;

    /** @var array List of whitelisted IP addresses that bypass policy checks */
    const WHITELIST = [
        '127.0.0.1',
        '::1',
        'localhost',
        // '132.176.117.197' Reverse setting for testing.
    ];

    /**
     * Check if the user has accepted the required tool policy.
     *
     * Checks against the configured policy version and optionally bypasses
     * the check based on whitelist or disable_blocking settings.
     *
     * @return bool True if the policy is accepted or bypassed, false otherwise
     */
    public static function tool_policy_accepted() {
        global $DB, $USER;
        if (self::DISABLE_BLOCKING === true || (self::DISABLE_WHITELIST === false && in_array($_SERVER['REMOTE_ADDR'], self::WHITELIST))) {
            return true;
        }
        $res = $DB->get_record("tool_policy_acceptances", ["policyversionid" => self::POLICY_VERSION, "userid" => (int)$USER->id ], "status");
        if (isset($res->status) && $res->status == 1) {
            return true;
        }
        return false;
    }
}
