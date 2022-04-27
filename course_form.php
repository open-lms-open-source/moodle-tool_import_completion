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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/user/lib.php');

class admin_import_completion_form extends moodleform {
    public function definition () {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $choices = array(0 => 'Completions', 1 => 'Grades');
        $mform->addElement('select', 'importing', get_string('importing', 'tool_import_completion'), $choices);

        $mform->addElement('filepicker', 'coursecompletionfile', get_string('file'));
        $mform->addRule('coursecompletionfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_import_completion'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_import_completion'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_import_completion'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $choices = $this->getAvailableProperties();
        $mform->addElement('select', 'mapping', get_string('mapping', 'tool_import_completion'), $choices);
        $mform->setDefault('mapping', 'userid');

        $dateformat = array('d/m/Y' => 'd/m/y 30/01/2019',
                            'm/d/Y' => 'm/d/y 01/30/2019',
                            'd-m-Y' => 'd-m-y 30-01-2019',
                            'm-d-Y' => 'm-d-y 01-30-2019',
                            'Y-m-d' => 'Y-m-d 2019-01-01',
                            'Y/m/d' => 'Y/m/d 2019/01/01',
                            'timestamp' => 'timestamp');
        $mform->addElement('select', 'dateformat', get_string('dateformat', 'tool_import_completion'), $dateformat);

        $this->add_action_buttons(false, get_string('importcompletion', 'tool_import_completion'));
    }

    private function getAvailableProperties() {
        // Will also be used by view to generate available options.

        $choices = array();
        $choices['userid'] = 'userid';
        $choices['username'] = 'username';
        $choices['email'] = 'email';
        return $choices;
    }
}

