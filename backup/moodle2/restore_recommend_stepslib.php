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
 * Define all the restore steps that will be used by the restore_recommend_activity_task
 *
 * @package   mod_recommend
 * @category  backup
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one recommend activity
 *
 * @package   mod_recommend
 * @category  backup
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_recommend_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('recommend', '/activity/recommend');
        $paths[] = new restore_path_element('recommend_question', '/activity/recommend/questions/question');

        if ($userinfo) {
            $paths[] = new restore_path_element('recommend_request', '/activity/recommend/requests/request');
            $paths[] = new restore_path_element('recommend_reply', '/activity/recommend/requests/request/replies/reply');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_recommend($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the recommend instance.
        $newitemid = $DB->insert_record('recommend', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_recommend_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->recommendid = $this->get_new_parentid('recommend');

        // Insert the entry record.
        $newitemid = $DB->insert_record('recommend_question', $data);
        $this->set_mapping('recommend_question', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_recommend_request($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->recommendid = $this->get_new_parentid('recommend');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->timerequested = $this->apply_date_offset($data->timerequested);
        $data->timecompleted = $this->apply_date_offset($data->timecompleted);

        // Insert the entry record.
        try {
            $newitemid = $DB->insert_record('recommend_request', $data);
        } catch (dml_exception $e) {
            // Duplicate of secret.
            $data->secret = mod_recommend_request_manager::generate_secret($data->userid, $data->email, $data->name);
            $newitemid = $DB->insert_record('recommend_request', $data);
        }
        $this->set_mapping('recommend_request', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_recommend_reply($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->recommendid = $this->get_new_parentid('recommend');
        $data->requestid = $this->get_new_parentid('recommend_request');
        $data->questionid = $this->get_mappingid('recommend_question', $data->questionid);

        // Insert the entry record.
        $newitemid = $DB->insert_record('recommend_reply', $data);
        $this->set_mapping('recommend_reply', $oldid, $newitemid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add recommend related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_recommend', 'intro', null);
    }
}
