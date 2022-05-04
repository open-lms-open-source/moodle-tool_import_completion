<?php

namespace tool_import_completion;

use tool_import_completion\file\csv_helper;
use tool_import_completion\file\csv_settings;
require_once($CFG->libdir . '/csvlib.class.php');
require(__DIR__ . '/../lib.php');

class external extends \external_api
{

    private const COMPONENT = 'user';
    private const FILE_AREA = 'private';

    public static function execute_file_parameters() {

        return new \external_function_parameters([
            'file' => new \external_single_structure(
                [
                    'filetype' => new \external_value(PARAM_TEXT, 'Import', VALUE_OPTIONAL ),
                    'filename' => new \external_value(PARAM_TEXT, 'Filename', VALUE_OPTIONAL),
                    'mapping' => new \external_value(PARAM_TEXT, 'Mapping attribute', VALUE_OPTIONAL),
                    'dateformat' => new \external_value(PARAM_TEXT, 'Date format', VALUE_OPTIONAL),
                    'csvdelimiter' => new \external_value(PARAM_TEXT, 'CSV Delimiter', VALUE_OPTIONAL),
                    'encoder' => new \external_value(PARAM_TEXT, 'Encoding type', VALUE_OPTIONAL),
                ],
            )
        ], 'File Object', VALUE_OPTIONAL);
    }

    public static function execute_file($rawparams) {
        global $USER;
        // Param validation.
        $params = [
            'file' => [
                'filetype' => $rawparams['filetype'],
                'filename' => $rawparams['filename'],
                'mapping' => $rawparams['mapping'],
                'dateformat' => $rawparams['dateformat'],
                'csvdelimiter' => $rawparams['csvdelimiter'],
                'encoder' => $rawparams['encoder'],
            ]
        ];
        $params = self::validate_parameters(self::execute_file_parameters(), $params);
        $badparameters = self::validate_file_parameters($params);
        if (!empty($badparameters)) {
            var_dump('ERROR');
            var_dump($badparameters);
            die();
        }
        $params['file']['filetype'] = csv_settings::get_filetype_code($params['file']['filetype']);

        // Security Check.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/import_completion:uploadrecords', $context);

        // Retrieve parameters.
        $dataimport = $params['file']['filetype'];
        $filename = $params['file']['filename'];
        $mapping = $params['file']['mapping'];
        $dateformat = $params['file']['dateformat'];
        $csvdelimiter = $params['file']['csvdelimiter'];
        $encoder = $params['file']['encoder'];

        // Retrieve file from private files.
        $fs = get_file_storage();
        $fileinfo = array(
            'component' => self::COMPONENT,
            'filearea' => self::FILE_AREA,
            'itemid' => 0,
            'contextid' => \context_user::instance($USER->id)->id,
            'filepath' => '/',
            'filename' => $filename);

        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

        // Load CSV file.
        $iid = \csv_import_reader::get_new_iid('import_completion');
        $cir = new \csv_import_reader($iid, 'import_completion');
        $readcount = $cir->load_csv_content($file->get_content(), $encoder, $csvdelimiter);

        // Validate CSV file columns.
        $helper = new csv_helper();
        $filecolumns = $helper->validate_csv_columns($cir);
        $filecolumns = implode(',', $filecolumns);

        // Upload the data in the DB.
        $uploadeddata = upload_data($filecolumns, $iid, $mapping, $dataimport, $dateformat, $readcount);

        // Prepare answer to client.
        $result = [
            'totaluploaded' => $uploadeddata['totaluploaded'],
            'totalupdated' => $uploadeddata['totalupdated'],
            'totalerrors' => $uploadeddata['totalerrors'],
            'totalrecords' => $uploadeddata['totalrecords'],
        ];

        return $result;
    }

    public static function execute_file_returns() {
        return new \external_single_structure([
            'totaluploaded' => new \external_value(PARAM_INT, ''),
            'totalupdated' => new \external_value(PARAM_INT, ''),
            'totalerrors' => new \external_value(PARAM_INT, ''),
            'totalrecords' => new \external_value(PARAM_INT, '')
        ]);
    }

    private static function validate_file_parameters($params) {
        $baddata = [];
        if (!csv_settings::validate_filetype($params['file']['filetype'])) {
            array_push($baddata, 'filetype');
        }
        if (!csv_settings::validate_mapping($params['file']['mapping'])) {
            array_push($baddata, 'mapping');
        }
        if (!csv_settings::validate_dateformat($params['file']['dateformat'])) {
            array_push($baddata, 'dateformat');
        }
        if (!csv_settings::validate_delimiter($params['file']['csvdelimiter'])) {
            array_push($baddata, 'csvdelimiter');
        }
        if (!csv_settings::validate_encoder($params['file']['encoder'])) {
            array_push($baddata, 'encoder');
        }
        return $baddata;
    }


}