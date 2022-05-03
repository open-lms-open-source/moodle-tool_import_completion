<?php

namespace tool_import_completion;

class external extends \external_api
{
    public static function execute_file_parameters() {

        return new \external_function_parameters([
            'file' => new \external_single_structure(
                [
                    'import' => new \external_value(PARAM_TEXT, 'Import', VALUE_OPTIONAL ),
                    'filename' => new \external_value(PARAM_TEXT, 'Filename', VALUE_OPTIONAL),
                    'mapping' => new \external_value(PARAM_TEXT, 'Mapping attribute', VALUE_OPTIONAL),
                    'dateformat' => new \external_value(PARAM_TEXT, 'Date format', VALUE_OPTIONAL),
                    'csvdelimiter' => new \external_value(PARAM_TEXT, 'CSV Delimiter', VALUE_OPTIONAL),
                    'encoding' => new \external_value(PARAM_TEXT, 'Encoding type', VALUE_OPTIONAL),
                ],
            )
        ], 'File Object', VALUE_OPTIONAL);
    }

    public static function execute_file($rawparams) {
        $params = [
            'file' => [
                'import' => $rawparams['import'],
                'filename' => $rawparams['filename'],
                'mapping' => $rawparams['mapping'],
                'dateformat' => $rawparams['dateformat'],
                'csvdelimiter' => $rawparams['csvdelimiter'],
                'encoding' => $rawparams['encoding'],
            ]
        ];

        $params = self::validate_parameters(self::execute_file_parameters(), $params);

        return true;
    }

    public static function execute_file_returns() {
        return new \external_value(PARAM_BOOL, 'Success status of the file execution');
    }


}