<?php

namespace tool_import_completion\form;

use tool_import_completion\file\csv_settings;
require_once($CFG->libdir.'/formslib.php');
class admin_import_completion_form extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $choices = csv_settings::FILE_TYPE_OPTIONS;
        $mform->addElement('select', 'importing', get_string('importing', 'tool_import_completion'), $choices);

        $mform->addElement('filepicker', 'coursecompletionfile', get_string('file'));
        $mform->addRule('coursecompletionfile', null, 'required');

        $choices = csv_settings::get_file_delimeters();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_import_completion'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = csv_settings::get_file_encoders();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_import_completion'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_import_completion'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $choices = csv_settings::MAPPING_OPTIONS;
        $mform->addElement('select', 'mapping', get_string('mapping', 'tool_import_completion'), $choices);
        $mform->setDefault('mapping', 'userid');

        $choices = csv_settings::COURSE_MAPPING_OPTIONS;
        $mform->addElement('select', 'coursemapping', get_string('coursemapping', 'tool_import_completion'), $choices);
        $mform->setDefault('coursemapping', 'courseid');

        $dateformat = csv_settings::DATEFORMAT_OPTIONS;
        $mform->addElement('select', 'dateformat', get_string('dateformat', 'tool_import_completion'), $dateformat);

        $this->add_action_buttons(false, get_string('importcompletion', 'tool_import_completion'));
    }

}