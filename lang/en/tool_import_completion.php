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
 * Plugin strings are defined here.
 *
 * @package     tool_import_completion
 * @category    string
 * @author   2019 Daniel Villareal <daniel@ecreators.com.au>, Lupiya Mujala <lupiya.mujala@ecreators.com.au>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Import Completion/Grades';
$string['importcompletion'] = 'Import Completion/Grades';
$string['importcompletion_help'] = 'Courses may be uploaded (and optionally enrolled in courses) via csv file. The format of the file should be as follows:
    * Each line of the file contains one record
    * Each record is a series of data separated by commas (or other delimiters)
    * Required fieldnames are userid, course, timecompleted';
$string['csvdelimiter'] = 'CSV delimiter';
$string['defaultvalues'] = 'Default values';
$string['deleteerrors'] = 'Delete errors';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview rows';
$string['uucsvline'] = 'CSV line';
$string['completiondate'] = 'Completion Date';
$string['mapping'] = 'User Mapping';
$string['status'] = 'Status';
$string['usernotfound'] = 'User not found';
$string['coursenotfound'] = 'Course not found';
$string['usernotenrolled'] = 'User not enrolled on the course';
$string['dateformat'] = 'Date Format';
$string['importing'] = 'Importing';
$string['dategraded'] = 'Date Graded';
$string['grade'] = 'Grade';
$string['coursemodule'] = 'Course Module';
$string['totalrecords'] = 'Total Records Submitted: {$a}';
$string['totaluploads'] = 'Total Records Uploaded: {$a}';
$string['totalerrors'] = 'Total Errors Uploading: {$a}';
$string['userid'] = 'User ID';
$string['courseid'] = 'Course ID';
$string['timeenrolled'] = 'Time Enrolled';
$string['timestarted'] = 'Time Started';
$string['timecompleted'] = 'Time Completed';
$string['reaggregate'] = 'Re-aggregate';
$string['results'] = 'Results';
$string['action'] = 'Action';
$string['timecompleted'] = 'Time Completed';
$string['coursemoduleid'] = 'Course Module ID';
$string['completionstate'] = 'Completion State';
$string['viewed'] = 'Viewed';
$string['itemid'] = 'Item ID';
$string['grades'] = 'Grades';
$string['modulecompletions'] = 'Course Module Completions';
$string['coursecompletions'] = 'Course Completions';
$string['upload_warning'] = 'WARNING: This plugin will override existing grades/completions.';
$string['error:invalidparameter'] = 'The request has invalid values in the following fields: {$a}';
$string['error:nopermission'] = 'The current user has no access to import completion/grades.';
$string['eventimport_finished'] = 'Import Completion/Grade ended';
$string['eventimport_finisheddesc'] = 'The process to update Completion/Grade has ended.';
$string['eventimport_started'] = 'Import Completion/Grade started';
$string['eventimport_starteddesc'] = 'Usar has uploaded a file in tool_import_completion plugin.';
$string['import_completion:uploadrecords'] = 'Upload completion/grade csv file to override data.';
$string['privacy:metadata'] = 'Completion import does not store user data.';
$string['coursemapping'] = 'Course mapping';
$string['userid'] = 'User ID';
$string['uploadcompletions'] = 'Upload Completions';
$string['uploadgrades'] = 'Upload Grades';
$string['gradeitemnotfound'] ='Grade Item not found';
