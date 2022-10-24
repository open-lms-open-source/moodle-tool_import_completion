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
 * This library includes the functions for import completion.
 *
 * @package     tool_import_completion
 * @category    admin
 * @author   2019 Daniel Villareal <daniel@ecreators.com.au>, Lupiya Mujala <lupiya.mujala@ecreators.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use tool_import_completion\output\completion_table;
require_once($CFG->libdir . '/gradelib.php');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

function upload_data($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount, $coursemapping) {
    global $DB, $CFG;
    $filecolumns = explode (',', $filecolumns);
    $linenum = 1;
    $cir = new csv_import_reader($iid, 'import_completion');
    $cir->init();
    $totalerror = 0;
    $totaluploaded = 0;
    $totalupdated = 0;
    $uploadedcompletions = array();
    $uploadedmodulecompletions = array();
    $uploadedgrades = array();
    $upgradecoursegrades = array();
    while ($line = $cir->next()) {
        $userid = 0;
        $course = 0;
        $timecompleted = 0;
        $dategraded = 0;
        $grade = 0;
        $gradeitem = '';
        $error = false;
        $moduleid = 0;
        foreach ($line as $keynum => $value) {
            // Get rid of trailing spaces for validation.
            $value = trim($value);
            $key = $filecolumns[$keynum];
            if ($key == $mapping) {
                if (strpos($mapping, 'profile_field_') !== false) {
                    $shortname = substr($mapping, 14);
                    $user = $DB->get_record_sql("SELECT U.*
                                                  FROM {user} U JOIN {user_info_data} UID ON UID.userid = U.id
                                                  JOIN {user_info_field} UIF ON UIF.id = UID.fieldid
                                                  WHERE UIF.shortname = ? AND UID.data = ?", array($shortname, $value));
                    if ($user) {
                        $userid = $user->id;
                    } else {
                        $error = true;
                    }
                } else {
                    if ($mapping == 'userid') {
                        $map = 'id';
                    } else {
                        $map = $mapping;
                    }
                    $user = $DB->get_record("user", array($map => $value));
                    if ($user) {
                        $userid = $user->id;
                    } else {
                        $error = true;
                    }
                }
            }
            if ($key == $coursemapping) {
                if ($coursemapping == 'course') {
                    $courses = $DB->get_record("course", ['id' => $value]);
                } else {
                    $courses = $DB->get_record("course", [$coursemapping => $value]);
                }

                if ($courses) {
                    $context = context_course::instance($courses->id);
                    if (!is_enrolled($context, $user) && $dataimport == 0) {
                        $error = true;
                        $course = $value. " [Not enrolled]";
                    } else {
                        $course = $courses->id;
                    }

                } else {
                    if ($dataimport == 0) {
                        $error = true;
                    }
                }
            }

            if ($key == 'timecompleted') {
                if ($dateformat == 'timestamp') {
                    $timecompleted = $value;
                } else {
                    $timezone = new DateTimeZone($CFG->timezone);
                    $timecompleted = DateTime::createFromFormat($dateformat, $value);
                    $timecompleted->setTime(0, 0);
                    $timecompleted->setTimezone($timezone);
                    $timecompleted = $timecompleted->getTimestamp();
                }

            }

            if ($key == 'timestarted' && !empty($value)) {
                if ($dateformat == 'timestamp') {
                    $timestarted = $value;
                } else {
                    $timezone = new DateTimeZone($CFG->timezone);
                    $timestarted = DateTime::createFromFormat($dateformat, $value);
                    $timestarted->setTime(0, 0);
                    $timestarted->setTimezone($timezone);
                    $timestarted = $timestarted->getTimestamp();
                }

            }

            if ($key == 'dategraded') {
                if ($dateformat == 'timestamp') {
                    $dategraded = $value;
                } else {
                    $timezone = new DateTimeZone($CFG->timezone);
                    $dategraded = DateTime::createFromFormat($dateformat, $value);
                    $dategraded->setTime(0, 0);
                    $dategraded->setTimezone($timezone);
                    $dategraded = $dategraded->getTimestamp();
                }
            }

            if ($key == 'moduleid') {
                $coursemodule = $DB->get_record("course_modules", array('id' => $value));
                if ($coursemodule) {
                    $module = $DB->get_record("modules", array('id' => $coursemodule->module));
                    if ($module) {
                        $grade = $DB->get_record("grade_items",
                            array('itemmodule' => $module->name, 'iteminstance' => $coursemodule->instance));
                        if ($grade) {
                            $gradeitem = $grade->id;
                            $moduleid = $value;
                        } else {
                            $error = true;
                            $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                        }
                    } else {
                        $error = true;
                        $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                    }
                } else {
                    $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                    $error = true;
                }
            }

            if ($key == 'grade') {
                $grade = $value;
            }

        }

        if (!$error) {
            // Logic for either course completion or grade.
            if ($dataimport == 0) {
                try {
                    $type = \tool_import_completion\importcompletionlib::mark_completed($userid, $course, $timecompleted, $timestarted, true);
                    $uploadedcompletions[] = ['userid' => $userid, 'course' => $course, 'timecompletedstring' => userdate($timecompleted), 'type' => $type];
                    $totalupdated++;
                } catch (\Exception $exception) {
                    $totalerror++;
                }
            } else {
                try {
                    $type = \tool_import_completion\importcompletionlib::set_grade_coursemodule($userid, $gradeitem, $grade, $dategraded);
                    $upgradecoursegrades[] = $DB->get_field('grade_items', 'courseid', ['id' => $gradeitem]);
                    $totalupdated++;
                    $uploadedgrades[] = [
                        'userid' => $userid,
                        'itemid' => $gradeitem,
                        'finalgrade' => $grade,
                        'type' => $type,
                        'timecreatedstring' => userdate($dategraded)
                    ];
                } catch (Exception $exception) {
                    $totalerror++;
                }

                // Update course module completion if time completed is added.
                if ($timecompleted > 0) {
                    try {
                        $type = \tool_import_completion\importcompletionlib::set_course_module_completed($userid, $moduleid, $timecompleted);
                        $uploadedmodulecompletions[]  = ['userid' => $userid, 'coursemoduleid' => $moduleid, 'type' => $type, 'timemodifiedstring' => userdate($timecompleted)];
                        $totaluploaded++;
                    } catch (Exception $exception) {
                        $totalerror++;
                    }
                }
            }
        } else {
            $totalerror++;
        }
        $linenum++;
    }

    // Update the final grades of the courses affected by some grades import.
    if (!empty($upgradecoursegrades)) {
        foreach ($upgradecoursegrades as $courseid) {
            $coursegradeitem = grade_item::fetch_course_item($courseid);
            $coursegradeitem->force_regrading();
            grade_regrade_final_grades($courseid);
        }
    }

    return array('totaluploaded' => $totaluploaded, 'totalupdated' => $totalupdated, 'totalerrors' => $totalerror,
        'uploadedcompletions' => $uploadedcompletions, 'moduleCompletions' => $uploadedmodulecompletions,
        'uploadedgrades' => $uploadedgrades, 'totalrecords' => ($linenum - 1));
}

function display_file_data($cir, $importing, $previewrows, $filecolumns, $mapping, $dateformat, $iid, $readcount, $coursemapping) {
    global $DB, $CFG;

    $cir->init();
    $upt = new completion_table();
    $linenum = 1;
    $upt->start($importing); // Start table.

    // Modify the columns for different import.
    if ($importing == 1) {
        $upt->columns = array('line', 'id', 'username', 'firstname', 'lastname', 'moduleid',
            'grade', 'dategraded', 'completiondate', 'status');
    }
    while ($linenum <= $previewrows and $line = $cir->next()) {

        $upt->flush();

        $upt->track('line', $linenum);
        foreach ($line as $keynum => $value) {

            $key = $filecolumns[$keynum];

            if ($key == $mapping) {
                if (strpos($mapping, 'profile_field_') !== false) {
                    $shortname = substr($mapping, 14);
                    $user = $DB->get_record_sql("SELECT U.*
                                                    FROM {user} U
                                                        JOIN {user_info_data} UID ON UID.userid = U.id
                                                        JOIN {user_info_field} UIF ON UIF.id = UID.fieldid
                                                        WHERE UIF.shortname = ? AND UID.data = ?", array($shortname, $value));
                } else {
                    if ($mapping == 'userid') {
                        $map = 'id';
                    } else {
                        $map = $mapping;
                    }
                    $user = $DB->get_record("user", [$map => $value]);
                }
                if ($user) {

                    $upt->track('id', $user->id, 'normal', true);
                    $upt->track('username', $user->username, 'normal', true);
                    $upt->track('firstname', $user->firstname, 'normal', true);
                    $upt->track('lastname', $user->lastname, 'normal', true);
                } else {
                    $upt->track('status', get_string('usernotfound', 'tool_import_completion'), 'normal');
                }
            }
            if ($key == $coursemapping) {
                if ($coursemapping == 'course') {
                    $course = $DB->get_record("course", ['id' => $value]);
                } else {
                    $course = $DB->get_record("course", [$coursemapping => $value]);
                }
                if ($course) {
                    $context = context_course::instance($course->id);
                    if (!is_enrolled($context, $user)) {
                        $upt->track('status', get_string('usernotenrolled', 'tool_import_completion'), 'normal');
                    } else {
                        $upt->track('course', $course->fullname, 'normal');
                        $upt->track('idnumber', $course->idnumber, 'normal');
                        $upt->track('shortname', $course->shortname, 'normal');
                    }

                } else {
                    $upt->track('status', get_string('coursenotfound', 'tool_import_completion'), 'normal');
                }
            }
            if ($key == 'timecompleted') {
                if ($dateformat == 'timestamp') {
                    $upt->track('completiondate', userdate($value), 'normal', false);
                } else {
                    $timezone = new DateTimeZone($CFG->timezone);
                    $timecompleted = DateTime::createFromFormat($dateformat, $value);
                    $timecompleted->setTime(0, 0);
                    $timecompleted->setTimezone($timezone);
                    $upt->track('completiondate', userdate($timecompleted->getTimestamp()), 'normal', false);
                }

            }
            if ($importing == 1) {
                if ($key == 'dategraded') {
                    if ($dateformat == 'timestamp') {
                        $upt->track('dategraded', userdate($value), 'normal', false);
                    } else {
                        $timezone = new DateTimeZone($CFG->timezone);
                        $dategraded = DateTime::createFromFormat($dateformat, $value);
                        $dategraded->setTime(0, 0);
                        $dategraded->setTimezone($timezone);
                        $upt->track('dategraded', userdate($dategraded->getTimestamp()), 'normal', false);
                    }

                }

                if ($key == 'grade') {
                    $upt->track('grade', $value, 'normal', false);
                }

                if ($key == 'moduleid') {
                    $gradeitem = '';
                    $coursemodule = $DB->get_record("course_modules", array('id' => $value));
                    if ($coursemodule) {
                        $module = $DB->get_record("modules", array('id' => $coursemodule->module));
                        if ($module) {
                            $grade = $DB->get_record("grade_items",
                                array('itemmodule' => $module->name, 'iteminstance' => $coursemodule->instance));
                            if ($grade) {
                                $gradeitem = $grade->itemname;
                            } else {
                                $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                            }
                        } else {
                            $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                        }
                    } else {
                        $gradeitem = get_string('gradeitemnotfound', 'tool_import_completion');
                    }
                    $upt->track('moduleid', $gradeitem, 'normal', false);
                }
            }
        }
        $linenum++;

    }
    $upt->close($iid, $filecolumns, $readcount, $mapping, $dateformat, $importing, $coursemapping);
}