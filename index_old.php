<?php
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
//require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('course_form.php');
require_once('locallib.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$uploadcompletion = optional_param('uploadcompletion', null, PARAM_RAW);
$filecolumns = optional_param('filecolumns', null, PARAM_RAW);
$readcount = optional_param('readcount', 0, PARAM_INT);
$mapping = optional_param('mapping', null, PARAM_RAW);
$dateformat = optional_param('dateformat', null, PARAM_RAW);
$importing = optional_param('importing', 10, PARAM_INT);
$dataimport = optional_param('dataimport', 0, PARAM_INT);

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

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


$PRF_FIELDS = array();

if ($proffields = $DB->get_records('user_info_field')) {
    foreach ($proffields as $key => $proffield) {
        $profilefieldname = 'profile_field_'.$proffield->shortname;
        $PRF_FIELDS[] = $profilefieldname;
        // Re-index $proffields with key as shortname. This will be
        // used while checking if profile data is key and needs to be converted (eg. menu profile field)
        $proffields[$profilefieldname] = $proffield;
        unset($proffields[$key]);
    }
}
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
        $filecolumns = completions_uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
    }

}else{
    $filecolumns = explode (',', $filecolumns);
    $linenum = 1;
    $cir = new csv_import_reader($iid, 'import_completion');
    $cir->init();
    $totalerror = 0;
    $totaluploaded = 0;
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
                $key = $filecolumns[$keynum];
                if ($key == $mapping) {
                    if (strpos($mapping, 'profile_field_') !== false) {
                        $shortname = substr($mapping, 14);
                        $user = $DB->get_record_sql("SELECT U.* 
                                                            FROM {user} U 
                                                                JOIN {user_info_data} UID ON UID.userid = U.id 
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
                            $userid = $value;
                        } else {
                            $error = true;
                        }
                    }


                }
                if ($key == 'course') {
                    $courses = $DB->get_record("course", array('id' => $value));
                    if ($courses) {
                        $context = context_course::instance($courses->id);
                        if (!is_enrolled($context, $user) && $dataimport==0) {
                            $error = true;
                            $course = $value. " [Not enrolled]";
                        } else {
                            $course = $value;
                        }

                    } else {
                        if($dataimport==0){
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
                    if($coursemodule){
                        $module = $DB->get_record("modules", array('id' => $coursemodule->module));
                        if($module){
                            $grade = $DB->get_record("grade_items", array('itemmodule' => $module->name, 'iteminstance' => $coursemodule->instance));
                            if($grade){
                                $gradeitem = $grade->id;
                                $moduleid = $value;
                            }else{
                                $error = true;
                                $gradeitem = 'Grade Item not found';
                            }
                        }else{
                            $error = true;
                            $gradeitem = 'Grade Item not found';
                        }
                    }else{
                        $gradeitem = 'Grade Item not found';
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
                    $completion = $DB->get_record("course_completions", array('userid' => $userid, 'course' => $course));
                    if ($completion) {
                        $completion->timecompleted = $timecompleted;
                        $DB->update_record('course_completions', $completion);
                        \core\event\course_completed::create_from_completion($completion)->trigger();
                    } else {
                        $courseCompletion = new stdClass();
                        $courseCompletion->userid = $userid;
                        $courseCompletion->course = $course;
                        $courseCompletion->timeenrolled = time();
                        $courseCompletion->timestarted = time();
                        $courseCompletion->timecompleted = $timecompleted;
                        $courseCompletion->reaggregate = 0;
                        $DB->insert_record('course_completions', $courseCompletion);
                        \core\event\course_completed::create_from_completion($courseCompletion)->trigger();
                    }
                }else{
                    $gradegrade = $DB->get_record_sql("SELECT GG.* FROM {grade_grades} GG INNER JOIN {grade_items} GI on GI.id = GG.itemid WHERE 
                                                            GG.itemid = :itemid AND  GG.userid = :userid", array('userid' => $userid, 'itemid' => $gradeitem));
                    if ($gradegrade) {
                        //echo "<br/><br/><br/><br/><br/><br/>grade item";
                        $gradegrade->finalgrade = $grade;
                        $gradegrade->timecreated = $dategraded;
                        $gradegrade->timemodified = $dategraded;
                        $DB->update_record('grade_grades', $gradegrade);
                        // Update course module completion if time completed is added.
                        if ($timecompleted>0){
                            $moduleComletion = $DB->get_record_sql("SELECT * FROM {course_modules_completion} WHERE userid = :userid AND  coursemoduleid= :coursemoduleid",
                                array('userid' => $userid, 'coursemoduleid' => $moduleid));
                            if($moduleComletion) {
                                $moduleComletion->timemodified = $timecompleted;
                                $DB->update_record('course_modules_completion', $moduleComletion);
                            }else {
                                $moduleComletion = new stdClass();
                                $moduleComletion->coursemoduleid = $moduleid;
                                $moduleComletion->userid = $userid;
                                $moduleComletion->completionstate = 1;
                                $moduleComletion->viewed = 1;
                                $moduleComletion->timemodified = $timecompleted;
                                $DB->insert_record('course_modules_completion', $moduleComletion);
                            }
                            // Update criteria completion if it exists or add a new one.
                            $coursemod = $DB->get_record('course_modules', array('id' => $moduleid));
                            $mod = $DB->get_record('modules', array('id' => $coursemod->module));
                            $crit = $DB->get_record('course_completion_criteria', array('module' => $mod->name, 'course' => $coursemod->course));
                            if($crit){
                                $critCompletion = $DB->get_record('course_completion_crit_compl', array('userid' => $userid, 'course' => $coursemod->course, 'criteriaid' => $crit->id));
                                if($critCompletion){
                                    $critCompletion->gradefinal = $grade;
                                    $critCompletion->timecompleted = $timecompleted;
                                    $DB->update_record('course_completion_crit_compl', $critCompletion);
                                }else{
                                    $critCompletion = new stdClass();
                                    $critCompletion->userid = $userid;
                                    $critCompletion->course = $coursemod->course;
                                    $critCompletion->criteriaid = $crit->id;
                                    $critCompletion->gradefinal = $grade;
                                    $critCompletion->timecompleted = $timecompleted;
                                    $DB->insert_record('course_completion_crit_compl', $critCompletion);
                                }
                            }
                        }
                    }else{
                        $gradeUpload = new stdClass();
                        $gradeUpload->userid = $userid;
                        $gradeUpload->itemid = $gradeitem;
                        $gradeUpload->finalgrade = $grade;
                        $gradeUpload->rawgrade = $grade;
                        $gradeUpload->timecreated = $dategraded;
                        $gradeUpload->timemodified = $dategraded;
                        $DB->insert_record('grade_grades', $gradeUpload);
                        // Add course module completion if time completed is added.
                        if ($timecompleted>0){
                            $moduleComletion = new stdClass();
                            $moduleComletion->coursemoduleid = $moduleid;
                            $moduleComletion->userid = $userid;
                            $moduleComletion->completionstate = 1;
                            $moduleComletion->viewed = 1;
                            $moduleComletion->timemodified = $timecompleted;
                            if($DB->insert_record('course_modules_completion', $moduleComletion)){
                                $coursemod = $DB->get_record('course_modules', array('id' => $moduleid));
                                $mod = $DB->get_record('modules', array('id' => $coursemod->module));
                                $crit = $DB->get_record('course_completion_criteria', array('module' => $mod->name, 'course' => $coursemod->course));
                                if($crit){
                                    $critCompletion = new stdClass();
                                    $critCompletion->userid = $userid;
                                    $critCompletion->course = $coursemod->course;
                                    $critCompletion->criteriaid = $crit->id;
                                    $critCompletion->gradefinal = $grade;
                                    $critCompletion->timecompleted = $timecompleted;
                                    $DB->insert_record('course_completion_crit_compl', $critCompletion);
                                }
                            }
                        }
                    }
                }
                $totaluploaded++;
            } else {
                $totalerror++;
            }
            $linenum++;

    }
    purge_all_caches();
    echo $OUTPUT->header();
    $readcount--;

    $text = "Completions";
    if ($dataimport==1){
        $text = "Grades";
    }
    echo "<p>Total Course " . $text . ": {$readcount}</p>";
    echo "<p>Total Uploaded Course " . $text . " : {$totaluploaded}</p>";
    echo "<p>Total Course " . $text . " with Error: {$totalerror}</p>";
    echo $OUTPUT->footer();
    die;

}



$cir->init();
$upt = new completions_uu_progress_tracker();
$linenum = 1;
echo $OUTPUT->header();
$upt->start($importing); // start table

//Modfy the columns for different import.
if ($importing==1){
    $upt->columns = array('line', 'id', 'username', 'firstname', 'lastname', 'moduleid', 'grade', 'dategraded', 'completiondate');
}
while ($linenum <= $previewrows and $line = $cir->next()) {

    $upt->flush();

    $upt->track('line', $linenum);
    foreach ($line as $keynum => $value) {

        $key = $filecolumns[$keynum];
        if($key == $mapping){
            if(strpos($mapping, 'profile_field_')!== false){
                $shortname = substr($mapping, 14);
                $user = $DB->get_record_sql("SELECT U.* 
                                                    FROM {user} U 
                                                        JOIN {user_info_data} UID ON UID.userid = U.id 
                                                        JOIN {user_info_field} UIF ON UIF.id = UID.fieldid 
                                                        WHERE UIF.shortname = ? AND UID.data = ?", array($shortname, $value));
            }else{
                if($mapping == 'userid'){
                    $map = 'id';
                }else{
                    $map = $mapping;
                }
                $user = $DB->get_record("user",array($map => $value));
            }
            if($user){

                $upt->track('id', $user->id, 'normal', true);
                $upt->track('username', $user->username, 'normal', true);
                $upt->track('firstname', $user->firstname, 'normal', true);
                $upt->track('lastname', $user->lastname, 'normal', true);
            }else{
                $upt->track('status', get_string('usernotfound', 'tool_import_completion'), 'normal');
            }
        }
        if($key == 'course'){
            $course = $DB->get_record("course",array('id' => $value));
            if($course){
                $context = context_course::instance($course->id);
                if(!is_enrolled($context, $user)){
                    $upt->track('status', get_string('usernotenrolled', 'tool_import_completion'), 'normal');
                }else{
                    $upt->track($key, $course->fullname, 'normal');
                }

            }else{
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
        if ($importing==1) {
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
                if($coursemodule){
                    $module = $DB->get_record("modules", array('id' => $coursemodule->module));
                    if($module){
                        $grade = $DB->get_record("grade_items", array('itemmodule' => $module->name, 'iteminstance' => $coursemodule->instance));
                        if($grade){
                            $gradeitem = $grade->itemname;
                        }else{
                            $gradeitem = 'Grade Item not found';
                        }
                    }else{
                        $gradeitem = 'Grade Item not found';
                    }
                }else{
                    $gradeitem = 'Grade Item not found';
                }
                $upt->track('moduleid', $gradeitem, 'normal', false);
            }
        }
    }

    // $upt->flush();
    $linenum++;
    // $user = $DB->get_record("user",array('id'=>))


}

$upt->close($iid,$filecolumns,$readcount,$mapping,$dateformat, $importing);
echo $OUTPUT->footer();
