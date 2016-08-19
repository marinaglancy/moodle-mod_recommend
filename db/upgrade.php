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
 * This file keeps track of upgrades to the recommend module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute recommend upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_recommend_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2016081400) {

        // Define field requesttemplatesubject to be added to recommend.
        $table = new xmldb_table('recommend');
        $field = new xmldb_field('requesttemplatesubject', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'completiononlyaccepted');

        // Conditionally launch add field requesttemplatesubject.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field requesttemplatebody to be added to recommend.
        $table = new xmldb_table('recommend');
        $field = new xmldb_field('requesttemplatebody', XMLDB_TYPE_TEXT, null, null, null, null, null, 'requesttemplatesubject');

        // Conditionally launch add field requesttemplatebody.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field requesttemplatebodyformat to be added to recommend.
        $table = new xmldb_table('recommend');
        $field = new xmldb_field('requesttemplatebodyformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'requesttemplatebody');

        // Conditionally launch add field requesttemplatebodyformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Recommend savepoint reached.
        upgrade_mod_savepoint(true, 2016081400, 'recommend');
    }

    if ($oldversion < 2016081700) {

        // Define field recommendid to be added to recommend_question.
        $table = new xmldb_table('recommend_question');
        $field = new xmldb_field('recommendid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field recommendid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key recommendid (foreign) to be added to recommend_question.
        $table = new xmldb_table('recommend_question');
        $key = new xmldb_key('recommendid', XMLDB_KEY_FOREIGN, array('recommendid'), 'recommend', array('id'));

        // Launch add key recommendid.
        $dbman->add_key($table, $key);

        // Recommend savepoint reached.
        upgrade_mod_savepoint(true, 2016081700, 'recommend');
    }

    return true;
}
