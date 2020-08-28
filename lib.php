<?php


$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

function getUserProfileFields(){
    global $DB;

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

    return $PRF_FIELDS;
}

function uploadData($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount){
    global $DB, $CFG;
    $filecolumns = explode (',', $filecolumns);
    $linenum = 1;
    $cir = new csv_import_reader($iid, 'import_completion');
    $cir->init();
    $totalerror = 0;
    $totaluploaded = 0;
    $totalUpdated = 0;
    $uploadedCompletions = array();
    $uploadedModuleCompletions = array();
    $uploadedGrades = array();
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
                    if($DB->update_record('course_completions', $completion)) {
                        $timecompleted = new DateTime();
                        $timecompleted->setTimestamp($completion->timecompleted);
                        $timecompleted->setTimeZone(new DateTimeZone($CFG->timezone));
                        $completion->timecompletedstring = $timecompleted->format('d/m/Y');
                        $completion->type = 'Update';
                        $uploadedCompletions[] = $completion;
                        $totalUpdated++;
                        \core\event\course_completed::create_from_completion($completion)->trigger();
                    }else{
                        $totalerror++;
                    }
                } else {
                    $courseCompletion = new stdClass();
                    $courseCompletion->userid = $userid;
                    $courseCompletion->course = $course;
                    $courseCompletion->timeenrolled = time();
                    $courseCompletion->timestarted = time();
                    $courseCompletion->timecompleted = $timecompleted;
                    $courseCompletion->reaggregate = 0;
                    if($DB->insert_record('course_completions', $courseCompletion)){
                        $timecompleted = new DateTime();
                        $timecompleted->setTimestamp($courseCompletion->timecompleted);
                        $timecompleted->setTimeZone(new DateTimeZone($CFG->timezone));
                        $courseCompletion->timecompletedstring = $timecompleted->format('d/m/Y');
                        $courseCompletion->type = 'Insert';
                        $uploadedCompletions[] = $courseCompletion;
                        $totaluploaded++;
                        \core\event\course_completed::create_from_completion($courseCompletion)->trigger();
                    }else{
                        $totalerror++;
                    }
                }
            }else{
                $gradegrade = $DB->get_record_sql("SELECT GG.* FROM {grade_grades} GG INNER JOIN {grade_items} GI on GI.id = GG.itemid WHERE 
                                                            GG.itemid = :itemid AND  GG.userid = :userid", array('userid' => $userid, 'itemid' => $gradeitem));
                if ($gradegrade) {

                    $gradegrade->finalgrade = $grade;
                    $gradegrade->timecreated = $dategraded;
                    $gradegrade->timemodified = $dategraded;
                    if($DB->update_record('grade_grades', $gradegrade)){
                        $timecreated = new DateTime();
                        $timecreated->setTimestamp($gradegrade->timecreated);
                        $timecreated->setTimeZone(new DateTimeZone($CFG->timezone));
                        $gradegrade->timecreatedstring = $timecreated->format('d/m/Y');
                        $timemodified = new DateTime();
                        $timemodified->setTimestamp($gradegrade->timemodified);
                        $timemodified->setTimeZone(new DateTimeZone($CFG->timezone));
                        $gradegrade->timecreatedstring = $timemodified->format('d/m/Y');
                        $gradegrade->type = 'Update';
                        $uploadedGrades[] = $gradegrade;
                        $totalUpdated++;
                    }else{
                        $totalerror++;
                    }
                    // Update course module completion if time completed is added.
                    if ($timecompleted>0){
                        $moduleComletion = $DB->get_record_sql("SELECT * FROM {course_modules_completion} WHERE userid = :userid AND  coursemoduleid= :coursemoduleid",
                            array('userid' => $userid, 'coursemoduleid' => $moduleid));
                        if($moduleComletion) {
                            $moduleComletion->timemodified = $timecompleted;
                            if($DB->update_record('course_modules_completion', $moduleComletion)){
                                $timemodified = new DateTime();
                                $timemodified->setTimestamp($moduleComletion->timemodified);
                                $timemodified->setTimeZone(new DateTimeZone($CFG->timezone));
                                $moduleComletion->timemodifiedstring = $timemodified->format('d/m/Y');
                                $moduleComletion->type =  'Update';
                                $uploadedModuleCompletions[] = $moduleComletion;
                                $totalUpdated++;
                            }else{
                                $totalerror++;
                            }
                        }
                    }
                }else{
                    $gradeUpload = new stdClass();
                    $gradeUpload->userid = $userid;
                    $gradeUpload->itemid = $gradeitem;
                    $gradeUpload->finalgrade = $grade;
                    $gradeUpload->timecreated = $dategraded;
                    $gradeUpload->timemodified = $dategraded;
                    if($DB->insert_record('grade_grades', $gradeUpload)){
                        $datecreated = new DateTime();
                        $datecreated->setTimestamp($gradeUpload->timecreated);
                        $datecreated->setTimeZone(new DateTimeZone($CFG->timezone));
                        $gradeUpload->datecreatedstring = $datecreated->format('d/m/Y');
                        $timemodified = new DateTime();
                        $timemodified->setTimestamp($gradeUpload->timemodified);
                        $timemodified->setTimeZone(new DateTimeZone($CFG->timezone));
                        $gradegrade->timecreatedstring = $timemodified->format('d/m/Y');
                        $gradeUpload->type =  'Insert';
                        $uploadedGrades[] = $gradeUpload;
                        $totaluploaded++;
                    }else{
                        $totalerror++;
                    }
                    // Add course module completion if time completed is added.
                    if ($timecompleted>0){
                        $moduleComletion = new stdClass();
                        $moduleComletion->coursemoduleid = $moduleid;
                        $moduleComletion->userid = $userid;
                        $moduleComletion->completionstate = 1;
                        $moduleComletion->viewed = 1;
                        $moduleComletion->timemodified = $timecompleted;
                        if($DB->insert_record('course_modules_completion', $moduleComletion)){
                            $timemodified = new DateTime();
                            $timemodified->setTimestamp($moduleComletion->timemodified);
                            $timemodified->setTimeZone(new DateTimeZone($CFG->timezone));
                            $moduleComletion->timemodifiedstring = $timemodified->format('d/m/Y');
                            $moduleComletion->type = 'Insert';
                            $uploadedModuleCompletions[] = $moduleComletion;
                            $totaluploaded++;
                        }else{
                            $totalerror++;
                        }
                    }
                }
            }
            //$totaluploaded++;
        } else {
            $totalerror++;
        }
        $linenum++;
    }

    purge_all_caches();

    return array('totaluploaded' => $totaluploaded, 'totalupdated' => $totalUpdated, 'totalerrors' => $totalerror, 'uploadedCompletions' => $uploadedCompletions, 'moduleCompletions' => $uploadedModuleCompletions,
                'uploadedGrades' => $uploadedGrades, 'totalrecords' => ($linenum-1));
}

function displayFileData($cir, $importing, $previewrows, $filecolumns, $mapping, $dateformat, $iid, $readcount){
    global $DB, $CFG;

    $cir->init();
    $upt = new completions_uu_progress_tracker();
    $linenum = 1;
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
        $linenum++;

    }

    $upt->close($iid,$filecolumns,$readcount,$mapping,$dateformat, $importing);
}