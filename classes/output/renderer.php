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

/**
 * Learnbook Import Completion
 *
 * @package    local_myteam
 * @author     2020 Lupiya, eCreators <lupiya@ecreators.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_import_completion\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;


class renderer  extends plugin_renderer_base{

    public function print_upload_results($data){

        $context = [
            'totaluploads' => get_string('totaluploads', 'tool_import_completion', ($data['totaluploaded'] + $data['totalupdated'])),
            'totalerrors' => get_string('totalerrors', 'tool_import_completion', $data['totalerrors']),
            'completions' => $data['uploadedCompletions'],
            'modules' => $data['moduleCompletions'],
            'grades' => $data['uploadedGrades'],
            'hasgrades' => count($data['uploadedGrades']),
            'hasmodules' => count($data['moduleCompletions']),
            'hascompletions' => count($data['uploadedCompletions']),
            'totalrecords' => get_string('totalrecords', 'tool_import_completion', $data['totalrecords'])
        ];
        return $this->render_from_template('tool_import_completion/results', $context);
    }
}