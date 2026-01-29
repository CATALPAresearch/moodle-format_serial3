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
 * Survey integration and tracking page
 *
 * @package    format_serial3
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');

$context = context_system::instance();
global $USER, $PAGE, $DB, $CFG;
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/course/format/serial3/survey.php');

$link = 'https://umfrage.fernuni-hagen.de/v3/';


if (!isset($_SESSION['s'])) {
    $_SESSION['s'] = new stdClass();
}

$sess = $_SESSION['s'];

if (isset($_GET['c'])) {
    $sess->c = $_GET['c'];
} else if (isset($_GET['a'], $_GET['s']) && is_numeric($_GET['a']) && is_numeric($_GET['s'])) {
    if (!isset($sess->s)) {
        $sess->s = [];
    }
    // @LS => ?s={SID}&a={SAVEDID}.
    $obj = new stdClass();
    $obj->survey_id = +$_GET['s'];
    $obj->submission_id = +$_GET['a'];
    $obj->complete_date = time();
    array_push($sess->s, $obj);
}

require_login();

if (isset($sess->s)) {
    if (is_array($sess->s)) {
        foreach ($sess->s as $survey) {
            $survey->user_id = $USER->id;
            $DB->insert_record('limesurvey_submissions', $survey);
        }
        // \core\notification::success("gespeichert").
    }
    unset($sess->s);
    $url = new moodle_url('/course/format/serial3/survey.php');
    redirect($url);
}

if (isset($sess->c)) {
    $id = 1;
    $records = $DB->get_records_sql('SELECT * FROM ' . $CFG->prefix . 'limesurvey_assigns WHERE course_id = ?', [+$sess->c]);
    $pending = false;
    $list = '';
    foreach ($records as $record) {
        if (isset($record->startdate) && is_int(+$record->startdate) && !is_null($record->startdate)) {
            if (time() < $record->startdate) {
                continue;
            }
        }

        if (isset($record->stopdate) && is_int(+$record->stopdate) && !is_null($record->stopdate)) {
            if (time() > $record->stopdate) {
                continue;
            };
        }

        $record->done = $DB->record_exists_sql('SELECT * FROM ' . $CFG->prefix . 'limesurvey_submissions WHERE user_id = ? AND survey_id = ?', [$USER->id, $record->survey_id]);
        // Insert values.
        $title = $record->name;
        if (isset($record->startdate) && !is_null($record->startdate) && is_int(+$record->startdate) && $record->startdate > 0) {
            $start = date("d.m.Y H:i", $record->startdate);
        } else {
            $start = "-";
        }

        if (isset($record->stopdate) && !is_null($record->stopdate) && is_int(+$record->stopdate) && $record->stopdate > 0) {
            $stop = date("d.m.Y H:i", $record->stopdate);
        } else {
            $stop = "-";
        }
        if (isset($record->warndate) && !is_null($record->warndate) && is_int(+$record->warndate) && $record->warndate > 0) {
            $warn = date("d.m.Y H:i", $record->warndate);
        } else {
            $warn = "-";
        }

        if ($record->done === true) {
            $state = "<span class=\"text-success font-weight-bold\">" . get_string('surveyDone', 'format_serial3') . "</span>";
        } else {
            $pending = true;
            if (isset($record->warndate) && is_int(+$record->warndate) && $record->warndate <= time()) {
                $state = "<span class=\"text-danger font-weight-bold\">" . get_string('surveyRequired', 'format_serial3') . "</span>";
            } else {
                $state = "<span class=\"text-warning font-weight-bold\">" . get_string('surveyPending', 'format_serial3') . "</span>";
            }
        }

        $surveyid = $record->survey_id;

        $list .= "<tr>
            <th class=\"align-middle\">{$id}</th>
                <td class=\"align-middle\">{$title}</td>
                <td class=\"align-middle\">{$stop}</td>
                <td class=\"align-middle\">{$state}</td>
                <td class=\"align-middle\"><button class=\"btn btn-primary center-block\" onClick=\"javascript:window.location.href='{$link}{$surveyid}'\">Zur Umfrage</button></td>
            </tr>";
        $id++;
        // <td class="align-middle">{$start}</td>  <td class="align-middle">{$warn}</td>.
    }

    if ($pending === false) {
        redirect(new moodle_url('/course/view.php', ['id' => +$sess->c]));
    }

    // OUTPUT.
    $PAGE->set_pagelayout('course');
    $PAGE->set_title(get_string('surveyTitle', 'format_serial3'));
    $PAGE->set_heading(get_string('surveyHeadline', 'format_serial3'));
    echo $OUTPUT->header();
    echo '<div style="display: inline-block;"><p class="mt-2 mb-4">' . get_string('surveyDescription', 'format_serial3') . '</p>';
    echo "<table class=\"table\">
                    <thead class=\"thead-light\">
                        <tr>
                            <th scope=\"col\">" . get_string('surveyID', 'format_serial3') . "</th>
                            <th scope=\"col\">" . get_string('surveyTitle', 'format_serial3') . "</th>
                            <th scope=\"col\">" . get_string('surveyStop', 'format_serial3') . "</th>
                            <th scope=\"col\">" . get_string('surveyState', 'format_serial3') . "</th>
                            <th scope=\"col\">" . get_string('surveyLink', 'format_serial3') . "</th>
                        </tr>
                    <thead>
                    <tbody>
                        {$list}
                    </tbody>
                </table>";
                // <th scope="col">" . get_string('surveyStart', 'format_serial3') . "</th><th scope="col">" . get_string('surveyWarn', 'format_serial3') . "</th>.

    $courseurl = new moodle_url('/course/view.php', ['id' => +$sess->c]);
    echo '<p class="text-center">' . get_string('surveyReqText', 'format_serial3') . '</p>';
    $buttonhtml = '<div class="text-center"><button onClick="javascript:window.location.href=\'' . $courseurl->__toString() . '\';"';
    $buttonhtml .= ' class="btn btn-secondary center-block">' . get_string('surveyButton', 'format_serial3') . '</button></div></div>';
    echo $buttonhtml;
    echo $OUTPUT->footer();
} else {
    redirect(new moodle_url('/'));
}
