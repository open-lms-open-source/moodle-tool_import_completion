<?php
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('course_form.php');
require_once('locallib.php');
require_once('lib.php');

use tool_import_completion\output;

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$uploadcompletion = optional_param('uploadcompletion', null, PARAM_RAW);
$filecolumns = optional_param('filecolumns', null, PARAM_RAW);
$readcount = optional_param('readcount', 0, PARAM_INT);
$mapping = optional_param('mapping', null, PARAM_RAW);
$dateformat = optional_param('dateformat', null, PARAM_RAW);
$importing = optional_param('importing', 10, PARAM_INT);
$dataimport = optional_param('dataimport', 0, PARAM_INT);

admin_externalpage_setup('toolimport_completion');
require_login();

$returnurl = new moodle_url('/admin/tool/import_completion/index.php');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

$STD_FIELDS = array('id', //optional record id
                    'userid', //required moodle user id
                    'course', //required moodle course id for completion record
                    'timeenrolled',
                    'timestarted',
                    'timecompleted', //required timecompleted in timestamp format
                    'reaggregate',
                    'dategraded',
                    'moduleid',
                    'grade'
);


$PRF_FIELDS = getUserProfileFields();

if(!$uploadcompletion){
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
                print_error('csvloaderror', '', $returnurl, $csvloaderror);
            }

            // test if columns ok
            $filecolumns = completions_uu_validate_import_completion_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

            // continue to form2

        } else {
            echo $OUTPUT->header();

            echo $OUTPUT->heading_with_help(get_string('importcompletion', 'tool_import_completion'), 'importcompletion', 'tool_import_completion');

            $mform->display();
            echo $OUTPUT->footer();
            die;
        }
    } else {
        $cir = new csv_import_reader($iid, 'import_completion');
        $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
    }

}else{
    $uploadedData = uploadData($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount);

    echo $OUTPUT->header();

    $renderer = $PAGE->get_renderer('tool_import_completion');
    $total = $uploadedData['totaluploaded'] + $uploadedData['totalupdated'];
    echo $renderer->print_upload_results($uploadedData);
    echo $OUTPUT->footer();

    die;
}




echo $OUTPUT->header();

displayFileData($cir, $importing, $previewrows, $filecolumns, $mapping, $dateformat, $iid, $readcount);

echo $OUTPUT->footer();
