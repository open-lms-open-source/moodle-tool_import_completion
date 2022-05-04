<?php

namespace tool_import_completion\file;

class csv_settings {

    const FILE_TYPE_OPTIONS = [ 0 => 'Completions',
        1 => 'Grades'];

    const MAPPING_OPTIONS = ['userid' => 'userid',
        'username' => 'username',
        'email' => 'email'];

    const DATEFORMAT_OPTIONS = [
        'd/m/Y' => 'd/m/y 30/01/2019',
        'm/d/Y' => 'm/d/y 01/30/2019',
        'd-m-Y' => 'd-m-y 30-01-2019',
        'm-d-Y' => 'm-d-y 01-30-2019',
        'Y-m-d' => 'Y-m-d 2019-01-01',
        'Y/m/d' => 'Y/m/d 2019/01/01',
        'timestamp' => 'timestamp'
    ];

    public static function get_file_delimeters(){
        return \csv_import_reader::get_delimiter_list();
    }

    public static function get_file_encoders() {
        return \core_text::get_encodings();
    }

    public static function validate_dateformat($value) {
        return in_array($value, self::DATEFORMAT_OPTIONS);
    }

    public static function validate_mapping($value) {
        return in_array($value, self::MAPPING_OPTIONS);
    }

    public static function validate_filetype($value) {
        return in_array($value, self::FILE_TYPE_OPTIONS);
    }

    public static function validate_delimiter($value) {
        $delimiters = self::get_file_delimeters();
        return array_key_exists($value, $delimiters);
    }

    public static function validate_encoder($value) {
        $encoders = self::get_file_encoders();
        return in_array($value, $encoders);
    }

    public static function get_filetype_code($value) {
        return array_search($value, self::FILE_TYPE_OPTIONS);
    }

}