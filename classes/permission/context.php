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
    function __construct($userid, $context) {
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
    public function isSiteAdmin() {
        return is_siteadmin($this->_userid);
    }

    /**
     * Check if user has manager role.
     *
     * @return bool True if user is manager
     */
    public function isManager() {
        return $this->findRole('manager');
    }

    /**
     * Check if user has course creator role.
     *
     * @return bool True if user is course creator
     */
    public function isCourseCreator() {
        return $this->findRole('coursecreator');
    }

    /**
     * Check if user has editing teacher role.
     *
     * @return bool True if user is editing teacher
     */
    public function isEditingTeacher() {
        return $this->findRole('editingteacher');
    }

    /**
     * Check if user has teacher role.
     *
     * @return bool True if user is teacher
     */
    public function isTeacher() {
        return $this->findRole('teacher');
    }

    /**
     * Check if user has student role.
     *
     * @return bool True if user is student
     */
    public function isStudent() {
        return $this->findRole('student');
    }

    /**
     * Check if user is a guest.
     *
     * @return bool True if user is guest
     */
    public function isGuest() {
        return is_guest($this->_context, $this->_userid);
    }

    /**
     * Check if user has user role.
     *
     * @return bool True if user has user role
     */
    public function isUser() {
        return $this->findRole('user');
    }

    /**
     * Check if context is frontpage.
     *
     * @return bool True if frontpage role exists
     */
    public function isFrontPage() {
        return $this->findRole('frontpage');
    }

    /**
     * Find a role by its shortname.
     *
     * @param string $shortname Role shortname to search for
     * @return bool True if role found
     */
    public function findRole($shortname) {
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
    public function hasViewCapability() {
        return is_viewing($this->_context, $this->_userid);
    }

    /**
     * Check if user is enrolled in context.
     *
     * @return bool True if user is enrolled
     */
    public function isEnrolled() {
        return is_enrolled($this->_context, $this->_userid);
    }

    /**
     * Check if user has any moderator-level role.
     *
     * @return bool True if user is site admin, manager, course creator, or editing teacher
     */
    public function isAnyKindOfModerator() {
        if (
            $this->isSiteAdmin() ||
            $this->isManager() ||
            $this->isCourseCreator() ||
            $this->isEditingTeacher() ||
            $this->isTeacher()
        ) {
            return true;
        }
        return false;
    }

    public function getContext() {
        return $this->_context;
    }

    public function getCourseContext() {
        $context = $this->_context;
        return $context->get_course_context();
    }
}
