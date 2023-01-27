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

use tool_import_completion\file\csv_helper;
use tool_import_completion\form\admin_import_completion_form;
use tool_import_completion\event\import_started;
use tool_import_completion\event\import_finished;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('lib.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$uploadcompletion = optional_param('uploadcompletion', null, PARAM_RAW);
$filecolumns = optional_param('filecolumns', null, PARAM_RAW);
$readcount = optional_param('readcount', 0, PARAM_INT);
$mapping = optional_param('mapping', null, PARAM_RAW);
$coursemapping = optional_param('coursemapping', null, PARAM_RAW);
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

if (!$uploadcompletion) {
    if (empty($iid)) {
        $mform = new admin_import_completion_form();

        if ($formdata = $mform->get_data()) {
            require_sesskey();
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
            $helper = new csv_helper();
            $filecolumns = $helper->validate_csv_columns($cir);

            $eventdata = [
                'context' => $context,
                'other' => array(
                    'filecolumns' => implode(',', $filecolumns),
                    'totallines' => $readcount,
                )
            ];
            $event = import_started::create($eventdata);
            $event->trigger();

            // Continue to form2.
        } else {
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
        $helper = new csv_helper();
        $filecolumns = $helper->validate_csv_columns($cir);
    }

} else {
    require_sesskey();
    $renderer = $PAGE->get_renderer('tool_import_completion');

    $uploadeddata = upload_data($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount, $coursemapping);

    $eventdata = [
        'context' => $context,
        'other' => array(
            'totaluploaded' => $uploadeddata['totaluploaded'],
            'totalupdated' => $uploadeddata['totalupdated'],
            'totalerrors' => $uploadeddata['totalerrors'],
            'totalrecords' => $uploadeddata['totalrecords'],
        )
    ];
    $event = import_finished::create($eventdata);
    $event->trigger();

    echo $OUTPUT->header();

    $total = $uploadeddata['totaluploaded'] + $uploadeddata['totalupdated'];
    echo $renderer->print_upload_results($uploadeddata);

    echo $OUTPUT->footer();
}

// Only display this if the file has been loaded.
if (!empty($iid) && !$uploadcompletion) {

    echo $OUTPUT->header();

    display_file_data($cir, $importing, $previewrows, $filecolumns, $mapping, $dateformat, $iid, $readcount, $coursemapping);

    echo $OUTPUT->footer();

}
