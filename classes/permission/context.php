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
 * Permission context helper class
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3\permission;

defined('MOODLE_INTERNAL') || die();

/**
 * Permission context helper class.
 *
 * Provides methods to check user roles and permissions within a context.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context {
    /** @var \context The context to check permissions in */
    protected $_context;

    /** @var int The user ID to check permissions for */
    protected $_userid;

    /** @var array The user's roles in this context */
    protected $_roles;

    /**
     * Constructor.
     *
     * @param int $userid User ID
     * @param \context $context Context to check permissions in
     */
    public function __construct($userid, $context) {
        require_login();
        $this->_context = $context;
        $this->_userid = $userid;
        $this->_roles = get_user_roles($this->_context, $this->_userid);
    }

    /**
     * Check if user is a site administrator.
     *
     * @return bool True if user is site admin
     */
    public function is_site_admin() {
        return is_siteadmin($this->_userid);
    }

    /**
     * Check if user has manager role.
     *
     * @return bool True if user is manager
     */
    public function is_manager() {
        return $this->find_role('manager');
    }

    /**
     * Check if user has course creator role.
     *
     * @return bool True if user is course creator
     */
    public function is_course_creator() {
        return $this->find_role('coursecreator');
    }

    /**
     * Check if user has editing teacher role.
     *
     * @return bool True if user is editing teacher
     */
    public function is_editing_teacher() {
        return $this->find_role('editingteacher');
    }

    /**
     * Check if user has teacher role.
     *
     * @return bool True if user is teacher
     */
    public function is_teacher() {
        return $this->find_role('teacher');
    }

    /**
     * Check if user has student role.
     *
     * @return bool True if user is student
     */
    public function is_student() {
        return $this->find_role('student');
    }

    /**
     * Check if user is a guest.
     *
     * @return bool True if user is guest
     */
    public function is_guest() {
        return is_guest($this->_context, $this->_userid);
    }

    /**
     * Check if user has user role.
     *
     * @return bool True if user has user role
     */
    public function is_user() {
        return $this->find_role('user');
    }

    /**
     * Check if context is frontpage.
     *
     * @return bool True if frontpage role exists
     */
    public function is_front_page() {
        return $this->find_role('frontpage');
    }

    /**
     * Find a role by its shortname.
     *
     * @param string $shortname Role shortname to search for
     * @return bool True if role found
     */
    public function find_role($shortname) {
        foreach ($this->_roles as $role) {
            if (isset($role->shortname) && strtolower($role->shortname) === strtolower($shortname)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has view capability.
     *
     * @return bool True if user can view
     */
    public function has_view_capability() {
        return is_viewing($this->_context, $this->_userid);
    }

    /**
     * Check if user is enrolled in context.
     *
     * @return bool True if user is enrolled
     */
    public function is_enrolled() {
        return is_enrolled($this->_context, $this->_userid);
    }

    /**
     * Check if user has any moderator-level role.
     *
     * @return bool True if user is site admin, manager, course creator, or editing teacher
     */
    public function is_any_kind_of_moderator() {
        if (
            $this->is_site_admin() ||
            $this->is_manager() ||
            $this->is_course_creator() ||
            $this->is_editing_teacher() ||
            $this->is_teacher()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the context instance.
     *
     * @return \context
     */
    public function get_context() {
        return $this->_context;
    }

    /**
     * Get the course context from this context.
     *
     * @return \context_course
     */
    public function get_course_context() {
        $context = $this->_context;
        return $context->get_course_context();
    }
}
