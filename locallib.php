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
 * Bulk user registration functions
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('COMPLETIONS_UU_USER_ADDNEW', 0);
define('COMPLETIONS_UU_USER_ADDINC', 1);
define('COMPLETIONS_UU_USER_ADD_UPDATE', 2);
define('COMPLETIONS_UU_USER_UPDATE', 3);

define('COMPLETIONS_UU_UPDATE_NOCHANGES', 0);
define('COMPLETIONS_UU_UPDATE_FILEOVERRIDE', 1);
define('COMPLETIONS_UU_UPDATE_ALLOVERRIDE', 2);
define('COMPLETIONS_UU_UPDATE_MISSING', 3);

define('COMPLETIONS_UU_BULK_NONE', 0);
define('COMPLETIONS_UU_BULK_NEW', 1);
define('COMPLETIONS_UU_BULK_UPDATED', 2);
define('COMPLETIONS_UU_BULK_ALL', 3);

define('COMPLETIONS_UU_PWRESET_NONE', 0);
define('COMPLETIONS_UU_PWRESET_WEAK', 1);
define('COMPLETIONS_UU_PWRESET_ALL', 2);

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function completions_uu_validate_import_completion_columns(csv_import_reader $cir, $stdfields,
                                                           $profilefields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // Test columns.
    $processed = array();
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // Standard fields are only lowercase.
            $newfield = $lcfield;

        } else if (in_array($field, $profilefields)) {
            // Exact profile field name match - these are case sensitive.
            $newfield = $field;

        } else if (in_array($lcfield, $profilefields)) {
            // Hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field.
            $newfield = $lcfield;

        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}

/**
 * Increments username - increments trailing number or adds it if not present.
 * Varifies that the new username does not exist yet
 * @param string $username
 * @return incremented username which does not exist yet
 */
function completions_uu_increment_username($username) {
    global $DB, $CFG;

    if (!preg_match_all('/(.*?)([0-9]+)$/', $username, $matches)) {
        $username = $username.'2';
    } else {
        $username = $matches[1][0] . ($matches[2][0] + 1);
    }

    if ($DB->record_exists('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
        return completions_uu_increment_username($username);
    } else {
        return $username;
    }
}

/**
 * Check if default field contains templates and apply them.
 * @param string template - potential tempalte string
 * @param object user object- we need username, firstname and lastname
 * @return string field value
 */
function completions_uu_process_template($template, $user) {
    if (is_array($template)) {
        // Hack for for support of text editors with format.
        $t = $template['text'];
    } else {
        $t = $template;
    }
    if (strpos($t, '%') === false) {
        return $template;
    }

    $username  = isset($user->username) ? $user->username : '';
    $firstname = isset($user->firstname) ? $user->firstname : '';
    $lastname  = isset($user->lastname) ? $user->lastname : '';

    $callback = partial('completions_uu_process_template_callback', $username, $firstname, $lastname);

    $result = preg_replace_callback('/(?<!%)%([+-~])?(\d)*([flu])/', $callback, $t);

    if (is_null($result)) {
        return $template; // Error during regex processing.
    }

    if (is_array($template)) {
        $template['text'] = $result;
        return $t;
    } else {
        return $result;
    }
}

/**
 * Internal callback function.
 */
function completions_uu_process_template_callback($username, $firstname, $lastname, $block) {
    switch ($block[3]) {
        case 'u':
            $repl = $username;
            break;
        case 'f':
            $repl = $firstname;
            break;
        case 'l':
            $repl = $lastname;
            break;
        default:
            return $block[0];
    }

    switch ($block[1]) {
        case '+':
            $repl = core_text::strtoupper($repl);
            break;
        case '-':
            $repl = core_text::strtolower($repl);
            break;
        case '~':
            $repl = core_text::strtotitle($repl);
            break;
    }

    if (!empty($block[2])) {
        $repl = core_text::substr($repl, 0 , $block[2]);
    }

    return $repl;
}

/**
 * Returns list of auth plugins that are enabled and known to work.
 *
 * If ppl want to use some other auth type they have to include it
 * in the CSV file next on each line.
 *
 * @return array type=>name
 */
function completions_uu_supported_auths() {
    // Get all the enabled plugins.
    $plugins = get_enabled_auth_plugins();
    $choices = array();
    foreach ($plugins as $plugin) {
        $objplugin = get_auth_plugin($plugin);
        // If the plugin can not be manually set skip it.
        if (!$objplugin->can_be_manually_set()) {
            continue;
        }
        $choices[$plugin] = get_string('pluginname', "auth_{$plugin}");
    }

    return $choices;
}

/**
 * Returns list of roles that are assignable in courses
 * @return array
 */
function completions_uu_allowed_roles() {
    // Let's cheat a bit, frontpage is guaranteed to exist and has the same list of roles.
    $roles = get_assignable_roles(context_course::instance(SITEID), ROLENAME_ORIGINALANDSHORT);
    return array_reverse($roles, true);
}

/**
 * Returns mapping of all roles using short role name as index.
 * @return array
 */
function completions_uu_allowed_roles_cache() {
    $allowedroles = get_assignable_roles(context_course::instance(SITEID), ROLENAME_SHORT);
    foreach ($allowedroles as $rid => $rname) {
        $rolecache[$rid] = new stdClass();
        $rolecache[$rid]->id   = $rid;
        $rolecache[$rid]->name = $rname;
        if (!is_numeric($rname)) { // Only non-numeric shortnames are supported.
            $rolecache[$rname] = new stdClass();
            $rolecache[$rname]->id   = $rid;
            $rolecache[$rname]->name = $rname;
        }
    }
    return $rolecache;
}

/**
 * Returns mapping of all system roles using short role name as index.
 * @return array
 */
function completions_uu_allowed_sysroles_cache() {
    $allowedroles = get_assignable_roles(context_system::instance(), ROLENAME_SHORT);
    foreach ($allowedroles as $rid => $rname) {
        $rolecache[$rid] = new stdClass();
        $rolecache[$rid]->id   = $rid;
        $rolecache[$rid]->name = $rname;
        if (!is_numeric($rname)) { // Only non-numeric shortnames are supported!
            $rolecache[$rname] = new stdClass();
            $rolecache[$rname]->id   = $rid;
            $rolecache[$rname]->name = $rname;
        }
    }
    return $rolecache;
}

/**
 * Pre process custom profile data, and update it with corrected value
 *
 * @param stdClass $data user profile data
 * @return stdClass pre-processed custom profile data
 */
function completions_uu_pre_process_custom_profile_data($data) {
    global $CFG, $DB;
    // Find custom profile fields and check if data needs to converted.
    foreach ($data as $key => $value) {
        if (preg_match('/^profile_field_/', $key)) {
            $shortname = str_replace('profile_field_', '', $key);
            if ($fields = $DB->get_records('user_info_field', array('shortname' => $shortname))) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $data->id);
                    if (method_exists($formfield, 'convert_external_data')) {
                        $data->$key = $formfield->convert_external_data($value);
                    }
                }
            }
        }
    }
    return $data;
}

/**
 * Checks if data provided for custom fields is correct
 * Currently checking for custom profile field or type menu
 *
 * @param array $data user profile data
 * @return bool true if no error else false
 */
function completions_uu_check_custom_profile_data(&$data) {
    global $CFG, $DB;
    $noerror = true;
    $testuserid = null;

    if (!empty($data['username'])) {
        if (preg_match('/id=(.*)"/i', $data['username'], $result)) {
            $testuserid = $result[1];
        }
    }
    // Find custom profile fields and check if data needs to converted.
    foreach ($data as $key => $value) {
        if (preg_match('/^profile_field_/', $key)) {
            $shortname = str_replace('profile_field_', '', $key);
            if ($fields = $DB->get_records('user_info_field', array('shortname' => $shortname))) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, 0);
                    if (method_exists($formfield, 'convert_external_data') &&
                            is_null($formfield->convert_external_data($value))) {
                        $data['status'][] = get_string('invaliduserfield', 'error', $shortname);
                        $noerror = false;
                    }
                    // Check for duplicate value.
                    if (method_exists($formfield, 'edit_validate_field') ) {
                        $testuser = new stdClass();
                        $testuser->{$key} = $value;
                        $testuser->id = $testuserid;
                        $err = $formfield->edit_validate_field($testuser);
                        if (!empty($err[$key])) {
                            $data['status'][] = $err[$key].' ('.$key.')';
                            $noerror = false;
                        }
                    }
                }
            }
        }
    }
    return $noerror;
}
