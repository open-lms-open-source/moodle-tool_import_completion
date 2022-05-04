<?php

namespace tool_import_completion\file;

use csv_import_reader;
use core_text;
class csv_helper {

    private const STANDARD_FIELDS = [
        'id', // Optional record id.
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
    ];

    private $prffields;

    function __construct() {
        $this->prffields = self::get_user_profile_fields();
    }

    /**
     * Validation callback function - verified the column line of csv file.
     * Converts standard column names to lowercase.
     * @param csv_import_reader $cir
     * @param array $stdfields standard user fields
     * @return array list of fields
     */
    public function validate_csv_columns($cir) {
        return $this->validate_import_completion_columns($cir);

    }

    /**
     * @return array
     * @throws \dml_exception
     */
    private static function get_user_profile_fields() {
        global $DB;

        $prffields = array();

        if ($proffields = $DB->get_records('user_info_field')) {
            foreach ($proffields as $key => $proffield) {
                $profilefieldname = 'profile_field_'.$proffield->shortname;
                $prffields[] = $profilefieldname;
                // Re-index $proffields with key as shortname. This will be
                // used while checking if profile data is key and needs to be converted (eg. menu profile field).
                $proffields[$profilefieldname] = $proffield;
                unset($proffields[$key]);
            }
        }

        return $prffields;
    }

    /**
     * Validation callback function - verified the column line of csv file.
     * Converts standard column names to lowercase.
     * @param csv_import_reader $cir
     * @return array list of fields
     */
    private function validate_import_completion_columns(csv_import_reader $cir) {
        $columns = $cir->get_columns();

        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error');
        }
        if (count($columns) < 2) {
            $cir->close();
            $cir->cleanup();
            print_error('csvfewcolumns', 'error');
        }

        // Test columns.
        $processed = array();
        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $lcfield = core_text::strtolower($field);
            if (in_array($field, self::STANDARD_FIELDS) or in_array($lcfield, self::STANDARD_FIELDS)) {
                // Standard fields are only lowercase.
                $newfield = $lcfield;

            } else if (in_array($field, $this->prffields)) {
                // Exact profile field name match - these are case sensitive.
                $newfield = $field;

            } else if (in_array($lcfield, $this->prffields)) {
                // Hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field.
                $newfield = $lcfield;

            } else {
                $cir->close();
                $cir->cleanup();
                print_error('invalidfieldname', 'error', null, $field);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                print_error('duplicatefieldname', 'error', null, $newfield);
            }
            $processed[$key] = $newfield;
        }

        return $processed;

    }


}