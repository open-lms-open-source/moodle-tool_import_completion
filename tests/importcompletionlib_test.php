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

class importcompletionlib_test extends \advanced_testcase {

    /**
     * @var $generator Data generator.
     */
    protected $generator;

    /**
     * Setup.
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->generator = $this->getDataGenerator();
    }

    public function testcoursecompletion_without_date() {
        global $DB;
        $course = $this->generator->create_course(['enablecompletion' => true]);
        $user = $this->generator->create_and_enrol($course);
        \tool_import_completion\importcompletionlib::mark_completed($user->id, $course->id);
        $ccompletion = new \completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertNotNull($DB->get_record('course_completions', ['userid' => $user->id, 'course' => $course->id]));
    }

    public function testcoursecompletion_on_date() {
        global $DB;
        $course = $this->generator->create_course(['enablecompletion' => true]);
        $user = $this->generator->create_and_enrol($course);
        $timecompleted = strtotime('10-05-2022');
        \tool_import_completion\importcompletionlib::mark_completed($user->id, $course->id, $timecompleted);
        $ccompletion = new \completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $this->assertEquals($ccompletion->timecompleted, $timecompleted);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertNotNull($DB->get_record('course_completions', ['userid' => $user->id, 'course' => $course->id]));
    }

    public function testcoursecompletion_override_date() {
        $course = $this->generator->create_course(['enablecompletion' => true]);
        $user = $this->generator->create_and_enrol($course);
        \tool_import_completion\importcompletionlib::mark_completed($user->id, $course->id);
        $ccompletion = new \completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $this->assertNotEmpty($ccompletion->timecompleted);
        $timecompletedupdated = strtotime('14-05-2022');
        \tool_import_completion\importcompletionlib::mark_completed($user->id, $course->id, $timecompletedupdated, null, true);
        $ccompletion = new \completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $this->assertEquals($ccompletion->timecompleted, $timecompletedupdated);
    }

    public function testcoursemodulecompletion_withoutdate() {
        global $DB;
        $course = $this->generator->create_course(['enablecompletion' => true]);
        $user = $this->generator->create_and_enrol($course);
        $pagegen = $this->generator->get_plugin_generator("mod_page");
        $pagemodule= $pagegen->create_instance(['course' => $course->id], ['completion' => 1]);

        $ccompletion = new \completion_info($course);

        $cm = get_coursemodule_from_id('', $pagemodule->cmid);
        $data = $ccompletion->get_data($cm, false, $user->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $data->completionstate);
        $timenow = time();
        \tool_import_completion\importcompletionlib::set_course_module_completed($user->id, $cm->id);
        $data = $ccompletion->get_data($cm, false, $user->id);

        $this->assertEquals(COMPLETION_COMPLETE, $data->completionstate);
        $this->assertEquals($timenow, $data->timemodified);
    }

    public function test_setgrade() {
        global $DB;
        $course = $this->generator->create_course(['enablecompletion' => true]);
        $user = $this->generator->create_and_enrol($course);
        $quizgen = $this->generator->get_plugin_generator("mod_quiz");
        $quizmodule= $quizgen->create_instance(['course' => $course->id], ['completion' => 1]);
        $coursemodule = $DB->get_record('course_modules', ['id' => $quizmodule->cmid]);
        $modulename = $DB->get_field('modules', 'name', ['id' => $coursemodule->module]);
        $gradeitemid = $DB->get_field('grade_items', 'id', ['itemmodule' => $modulename, 'iteminstance' => $quizmodule->id]);

        $gradeitemparams = [
            'id' => $gradeitemid
        ];
        $gradeitem = \grade_item::fetch($gradeitemparams);
        $gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $user->id]);
        $this->assertEmpty($gradegrade);
        $type = \tool_import_completion\importcompletionlib::set_grade_coursemodule($user->id, $gradeitemid, 75);

        $gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $user->id]);
        $this->assertNotEmpty($gradegrade);
        $this->assertEquals($gradegrade->finalgrade, 75);
        $this->assertNotEmpty($gradegrade->overridden);
        $this->assertEquals($type, 'insert');
        $type = \tool_import_completion\importcompletionlib::set_grade_coursemodule($user->id, $gradeitemid, 85);
        $gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $user->id]);
        $this->assertEquals($type, 'update');
        $this->assertEquals($gradegrade->finalgrade, 85);
        $this->assertNotEmpty($gradegrade->overridden);
    }
}

