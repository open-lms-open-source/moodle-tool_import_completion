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

use tool_import_completion\external\tool_import_completion_execute_file;

$functions = array(
    'tool_import_completion_execute_file' => array(         //web service function name
        'classname'   => 'tool_import_completion\external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'execute_file',          //external function name,
        'classpath' => '',
        // defaults to the service's externalib.php
        'description' => 'It uses a file uploaded in private file area and upload it to load grades or completions in the platform.',    //human readable description of the web service function
        'type'        => 'write',                  //database rights of the web service function (read, write)
        'ajax' => False,        // is the service available to 'internal' ajax calls.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
);