<?php

namespace tool_import_completion;

class importcompletionlib {
    
    /**
     * Mark course completed for a particular user
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param integer $timecomplete Time completed (optional).
     * @param integer $timestarted Time started (optional).
     * @param bool $override Override dates if already exists
     * @return string completion update or insert
     */
    public static function mark_completed(int $userid, int $courseid, int $timecompleted = null,
            int $timestarted = null, bool $override = false) : string {
        global $CFG;
        $type = 'insert';
        require_once($CFG->libdir.'/completionlib.php');
        $ccompletion = new \completion_completion(['userid' => $userid, 'course' => $courseid]);
        if ($override && $timecompleted && $ccompletion->timecompleted !== null) {
            $ccompletion->timecompleted = null;
            $ccompletion->update();
            $type = 'update';
        }
        if ($override && $timestarted && $ccompletion->timestarted !== null) {
            // Cannot use $ccompletion->mark_inprogress because it won't override timestarted and also cannot set it to null.
            $ccompletion->timestarted = $timestarted;
            $ccompletion->update();
        }
        $ccompletion->mark_complete($timecompleted);
        return $type;
    }

    /**
     * Mark course module as completed for a particular user
     *
     * @param int $userid User id.
     * @param int $coursemoduleid Course module id.
     * @param integer $timecompleted Time completed (optional).
     */
    public static function set_course_module_completed(int $userid, int $coursemoduleid, int $timecompleted = null) : string {
        global $DB, $CFG;
        $type = 'insert';
        require_once($CFG->libdir.'/completionlib.php');
        // Add caching for getcoursemoduleid , getcourse, completion info
        $cm = get_coursemodule_from_id('', $coursemoduleid);
        $courseid = $cm->course;
        $course = get_course($courseid);
        $ccompletion = new \completion_info($course);
        $activitycmpdata = $ccompletion->get_data($cm, false, $userid);
        if ($activitycmpdata->completionstate == COMPLETION_COMPLETE) {
            $type = 'update';
        }
        $activitycmpdata->completionstate = COMPLETION_COMPLETE;
        $activitycmpdata->timemodified = $timecompleted ? $timecompleted : time();
        $ccompletion->internal_set_data($cm, $activitycmpdata);
        return $type;
    }

    /**
     * Set the grade for user and coursemodule
     *
     * @param int $userid User id.
     * @param int $gradeitemid Grade itemid.
     * @param float $grade Grade.
     * @return string update or insert
     */
    public static function set_grade_coursemodule(int $userid, int $gradeitemid, float $grade, int $dategraded = null): string {
        global $DB;
        $type = 'update';
        $gradeitemparams = [
            'id' => $gradeitemid
        ];
        $gradeitem = \grade_item::fetch($gradeitemparams);
        $gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $userid]);
        if (!$gradegrade) {
            $gradegrade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $userid]);
            $gradegrade->insert();
            $type = 'insert';
        }

        $gradegrade->finalgrade = $grade;
        $gradegrade->timecreated = $dategraded ? $dategraded : time();
        $gradegrade->timemodified = $dategraded ? $dategraded : time();
        $gradegrade->set_overridden(true);
        $gradegrade->update($dategraded);
        return $type;
    }
}