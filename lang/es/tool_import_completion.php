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
 * Plugin strings are defined here.
 *
 * @package     tool_import_completion
 * @category    string
 * @author   2019 Daniel Villareal <daniel@ecreators.com.au>, Lupiya Mujala <lupiya.mujala@ecreators.com.au>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Importar finalización/calificaciones';
$string['importcompletion'] = 'Importar finalización/calificaciones';
$string['importcompletion_help'] = 'Los cursos se pueden cargar (y, opcionalmente, inscribirse en los cursos) a través de un '.
    'archivo csv. El formato del archivo debe ser el siguiente:<br>'.
    '* Cada línea del archivo contiene un registro'.
    '* Cada registro es una serie de datos separados por comas (u otros delimitadores)'.
    '* Los nombres de campo obligatorios son userid, course, timecompleted';
$string['csvdelimiter'] = 'Delimitador CSV';
$string['defaultvalues'] = 'Valores predeterminados';
$string['deleteerrors'] = 'Eliminar errores';
$string['encoding'] = 'Codificación';
$string['rowpreviewnum'] = 'Vista previa de filas';
$string['uucsvline'] = 'Línea CSV';
$string['completiondate'] = 'Fecha de Terminación';
$string['mapping'] = 'Mapeo de usuarios';
$string['status'] = 'Estado';
$string['usernotfound'] = 'Usuario no encontrado';
$string['coursenotfound'] = 'Curso no encontrado';
$string['usernotenrolled'] = 'Usuario no inscrito en el curso';
$string['dateformat'] = 'Formato de fecha';
$string['importing'] = 'Importando';
$string['dategraded'] = 'Fecha de calificación';
$string['grade'] = 'Calificación';
$string['coursemodule'] = 'Módulo del curso';
$string['totalrecords'] = 'Total de registros enviados: {$a}';
$string['totaluploads'] = 'Total de registros cargados:{$a}';
$string['totalerrors'] = 'Total de errores al cargar: {$a}';
$string['userid'] = 'ID del usuario';
$string['courseid'] = 'ID del curso';
$string['timeenrolled'] = 'Tiempo matriculado';
$string['timestarted'] = 'Hora de inicio';
$string['timecompleted'] = 'Tiempo completado';
$string['reaggregate'] = 'Reagregar';
$string['results'] = 'Resultados';
$string['action'] = 'Acción';
$string['timecompleted'] = 'Tiempo completado';
$string['coursemoduleid'] = 'ID del módulo del curso';
$string['completionstate'] = 'Estado de finalización';
$string['viewed'] = 'Visto';
$string['itemid'] = 'ID del Item';
$string['grades'] = 'Calificaciones';
$string['modulecompletions'] = 'Finalizaciones del módulo del curso';
$string['coursecompletions'] = 'Finalizaciones del curso';
$string['error:nopermission'] = 'El usuario no tiene permisos para importar finalizaciones/calificaciones.';
$string['import_completion:uploadrecords'] = 'Subir archivo csv con finalizaciones/calificaciones para sobreescribir los datos..';