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

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('course_form.php');
require_once('locallib.php');
require_once('lib.php');

defined('MOODLE_INTERNAL') || die();

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$uploadcompletion = optional_param('uploadcompletion', null, PARAM_RAW);
$filecolumns = optional_param('filecolumns', null, PARAM_RAW);
$readcount = optional_param('readcount', 0, PARAM_INT);
$mapping = optional_param('mapping', null, PARAM_RAW);
$dateformat = optional_param('dateformat', null, PARAM_RAW);
$importing = optional_param('importing', 10, PARAM_INT);
$dataimport = optional_param('dataimport', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('tool/import_completion:uploadrecords', $context);
$managerurl = new moodle_url('/admin/tool/import_completion/index.php');

$PAGE->set_context($context);
$PAGE->set_url($managerurl);
$PAGE->set_pagelayout('admin');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

$STD_FIELDS = array('id', // Optional record id.
    'userid', // Required moodle user id.
    'course', // Required moodle course id for completion record.
    'email',
    'username',
    'timeenrolled',
    'timestarted',
    'timecompleted', // Required timecompleted in timestamp format.
    'reaggregate',
    'dategraded',
    'moduleid',
    'grade'
);

$PRF_FIELDS = getUserProfileFields();

if (!$uploadcompletion) {
    if (empty($iid)) {
        $mform = new admin_import_completion_form();

        if ($formdata = $mform->get_data()) {
            $iid = csv_import_reader::get_new_iid('import_completion');
            $cir = new csv_import_reader($iid, 'import_completion');

            $content = $mform->get_file_content('coursecompletionfile');


            $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);

            $csvloaderror = $cir->get_error();
            unset($content);

            if (!is_null($csvloaderror)) {
                print_error('csvloaderror', '', $managerurl, $csvloaderror);
            }

            // Test if columns ok.
            $filecolumns = completions_uu_validate_import_completion_columns($cir, $STD_FIELDS, $PRF_FIELDS, $managerurl);

            // Continue to form2.
        } else {
            $PAGE->requires->css('/admin/tool/import_completion/assets/css/style.css');

            echo $OUTPUT->header();

            $renderer = $PAGE->get_renderer('tool_import_completion');

            echo $renderer->print_upload_warning();

            echo $OUTPUT->heading_with_help(get_string('importcompletion', 'tool_import_completion'),
                'importcompletion', 'tool_import_completion');
            $mform->display();

            echo $OUTPUT->footer();
        }
    } else {
        $cir = new csv_import_reader($iid, 'import_completion');
        $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $managerurl);
    }

} else {
    $renderer = $PAGE->get_renderer('tool_import_completion');

    $uploadedData = uploadData($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount);

    echo $OUTPUT->header();

    $total = $uploadedData['totaluploaded'] + $uploadedData['totalupdated'];
    echo $renderer->print_upload_results($uploadedData);

    echo $OUTPUT->footer();
}

// Only display this if the file has been loaded.
if (!empty($iid) && !$uploadcompletion) {

    echo $OUTPUT->header();

    displayFileData($cir, $importing, $previewrows, $filecolumns, $mapping, $dateformat, $iid, $readcount);

    echo $OUTPUT->footer();

}
