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
 * Tests for survey functionality.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_serial3;

/**
 * Test cases for survey page.
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_test extends \advanced_testcase {

    /**
     * Test survey.php file exists.
     */
    public function test_survey_file_exists() {
        global $CFG;
        
        $surveyFile = $CFG->dirroot . '/course/format/serial3/survey.php';
        $this->assertFileExists($surveyFile);
    }

    /**
     * Test survey.php has required structure.
     */
    public function test_survey_has_required_structure() {
        global $CFG;
        
        $surveyContent = file_get_contents($CFG->dirroot . '/course/format/serial3/survey.php');
        
        $this->assertStringContainsString('require_once', $surveyContent);
        $this->assertStringContainsString('require_login', $surveyContent);
        $this->assertStringContainsString('$PAGE', $surveyContent);
        $this->assertStringContainsString('context_system', $surveyContent);
    }

    /**
     * Test survey page uses sessions.
     */
    public function test_survey_uses_sessions() {
        global $CFG;
        
        $surveyContent = file_get_contents($CFG->dirroot . '/course/format/serial3/survey.php');
        
        $this->assertStringContainsString('$_SESSION', $surveyContent);
    }

    /**
     * Test survey handles GET parameters.
     */
    public function test_survey_handles_get_parameters() {
        global $CFG;
        
        $surveyContent = file_get_contents($CFG->dirroot . '/course/format/serial3/survey.php');
        
        $this->assertStringContainsString('$_GET', $surveyContent);
    }

    /**
     * Test survey interacts with database.
     */
    public function test_survey_uses_database() {
        global $CFG;
        
        $surveyContent = file_get_contents($CFG->dirroot . '/course/format/serial3/survey.php');
        
        $this->assertStringContainsString('$DB', $surveyContent);
        $this->assertStringContainsString('limesurvey', $surveyContent);
    }

    /**
     * Test survey page sets proper page properties.
     */
    public function test_survey_sets_page_properties() {
        global $CFG;
        
        $surveyContent = file_get_contents($CFG->dirroot . '/course/format/serial3/survey.php');
        
        $this->assertStringContainsString('set_context', $surveyContent);
        $this->assertStringContainsString('set_url', $surveyContent);
    }
}
