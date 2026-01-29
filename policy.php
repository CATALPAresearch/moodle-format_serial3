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
 * Policy consent and guidelines page
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');

$context = context_system::instance();
global $USER, $PAGE, $DB, $CFG;
require_login();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/course/format/serial3/policy.php');
$PAGE->set_pagelayout('course');
$PAGE->set_title("Zustimmung und Richtlinien");
echo $OUTPUT->header();

global $DB, $USER;
$message = '';

// Track the previous page to go back after the changes.
$policyback = $CFG->wwwroot;
if (isset($_SESSION['policy_back'])) {
    if (isset($_SERVER['HTTP_REFERER']) && !is_null($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'policy.php') === false) {
        $policyback = $_SERVER['HTTP_REFERER'];
        $_SESSION['policy_back'] = $policyback;
    } else {
        $policyback = $_SESSION['policy_back'];
    }
} else {
    if (isset($_SERVER['HTTP_REFERER']) && !is_null($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'policy.php') === false) {
        $policyback = $_SERVER['HTTP_REFERER'];
        $_SESSION['policy_back'] = $policyback;
    }
}

// change policy status
if (isset($_GET['policy']) && isset($_GET['status']) && isset($_GET['version'])) {
    $entry = $DB->get_record(
        "tool_policy_acceptances",
        [
            "userid" => (int)$USER->id,
            "policyversionid" => (int)$_GET['version'],
        ]
    );

    $time = time();
    if ($entry === false) {
        $lang = 'de_feu';
        $sql = '
            INSERT INTO ' . $CFG->prefix . 'tool_policy_acceptances (
                policyversionid,
                userid,
                status,
                lang,
                usermodified,
                timecreated,
                timemodified
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ';
        $res = $DB->execute(
            $sql,
            [
                (int)$_GET['version'],
                (int)$USER->id,
                (int)$_GET['status'],
                $lang,
                (int)$USER->id,
                $time,
                $time,
            ]
        );
    } else {
        $sql = '
            UPDATE ' . $CFG->prefix . 'tool_policy_acceptances
            SET status=?, timemodified=?
            WHERE policyversionid=? AND userid=?';
        $res = $DB->execute($sql, [(int)$_GET['status'], $time, (int)$_GET['version'], (int)$USER->id]);
    }

    $message = 'Eine Richtlinie wurde aktualisiert.';
}

// fetch policies
$query = '
SELECT v.name,
		a.status,
		a.timecreated as acceptance,
		v.timecreated as creation,
		p.id as id,
		v.id as version
FROM ' . $CFG->prefix . 'tool_policy as p
LEFT JOIN ' . $CFG->prefix . 'tool_policy_acceptances as a
ON p.currentversionid = a.policyversionid
AND a.userid = ?
INNER JOIN ' . $CFG->prefix . 'tool_policy_versions as v
ON p.currentversionid = v.id
';
$res = $DB->get_records_sql($query, [(int)$USER->id]);

// Display message if policy was updated
if (!empty($message)) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}

// Display policies
echo '<div class="policy-container">';
echo '<h2>' . get_string('pluginname', 'format_serial3') . ' - Policies</h2>';

if (!empty($res)) {
    echo '<table class="table">';
    echo '<thead><tr>';
    echo '<th>Policy Name</th>';
    echo '<th>Status</th>';
    echo '<th>Created</th>';
    echo '<th>Accepted</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($res as $policy) {
        echo '<tr>';
        echo '<td>' . format_string($policy->name) . '</td>';
        echo '<td>' . ($policy->status == 1 ? 'Accepted' : 'Pending') . '</td>';
        echo '<td>' . ($policy->creation ? userdate($policy->creation) : '-') . '</td>';
        echo '<td>' . ($policy->acceptance ? userdate($policy->acceptance) : '-') . '</td>';
        echo '<td>';
        
        if ($policy->status != 1) {
            $acceptUrl = new moodle_url('/course/format/serial3/policy.php', [
                'policy' => $policy->id,
                'version' => $policy->version,
                'status' => 1
            ]);
            echo '<a href="' . $acceptUrl . '" class="btn btn-primary btn-sm">Accept</a> ';
        }
        
        $declineUrl = new moodle_url('/course/format/serial3/policy.php', [
            'policy' => $policy->id,
            'version' => $policy->version,
            'status' => 0
        ]);
        echo '<a href="' . $declineUrl . '" class="btn btn-secondary btn-sm">Decline</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<p>No policies found.</p>';
}

echo '<div class="mt-3">';
echo '<a href="' . $policyback . '" class="btn btn-secondary">Back to Course</a>';
echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
