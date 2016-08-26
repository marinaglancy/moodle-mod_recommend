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
 * Library of interface functions and constants for module recommend
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function recommend_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        //case FEATURE_GRADE_HAS_GRADE:
        //    return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        //case FEATURE_COMPLETION_TRACKS_VIEWS:
        //    return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the recommend into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $recommend Submitted data from the form in mod_form.php
 * @param mod_recommend_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted recommend record
 */
function recommend_add_instance(stdClass $recommend, mod_recommend_mod_form $mform = null) {
    global $DB;

    $recommend->timecreated = time();
    $recommend->timemodified = time();

    if (isset($recommend->requesttemplatebodyeditor)) {
        $recommend->requesttemplatebody = $recommend->requesttemplatebodyeditor['text'];
        $recommend->requesttemplatebodyformat = $recommend->requesttemplatebodyeditor['format'];
        unset($recommend->requesttemplatebodyeditor);
    }

    $recommend->id = $DB->insert_record('recommend', $recommend);

    //recommend_grade_item_update($recommend);

    return $recommend->id;
}

/**
 * Updates an instance of the recommend in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $recommend An object from the form in mod_form.php
 * @param mod_recommend_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function recommend_update_instance(stdClass $recommend, mod_recommend_mod_form $mform = null) {
    global $DB;

    $recommend->timemodified = time();
    $recommend->id = $recommend->instance;

    if (isset($recommend->requesttemplatebodyeditor)) {
        $recommend->requesttemplatebody = $recommend->requesttemplatebodyeditor['text'];
        $recommend->requesttemplatebodyformat = $recommend->requesttemplatebodyeditor['format'];
        unset($recommend->requesttemplatebodyeditor);
    }

    $result = $DB->update_record('recommend', $recommend);

    //recommend_grade_item_update($recommend);

    return $result;
}

/**
 * Removes an instance of the recommend from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function recommend_delete_instance($id) {
    global $DB;

    if (! $recommend = $DB->get_record('recommend', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('recommend_reply', ['recommendid' => $recommend->id]);
    $DB->delete_records('recommend_request', ['recommendid' => $recommend->id]);
    $DB->delete_records('recommend_question', ['recommendid' => $recommend->id]);
    $DB->delete_records('recommend', array('id' => $recommend->id));

    recommend_grade_item_delete($recommend);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $recommend The recommend instance record
 * @return stdClass|null
 */
function recommend_user_outline($course, $user, $mod, $recommend) {
    $return = (object)['time' => 0, 'info' => ''];
    $cm = cm_info::create($mod, $user->id);
    $manager = new mod_recommend_request_manager($cm, $recommend);
    if ($requests = $manager->get_requests_by_status()) {
        foreach ($requests as $status => $cnt) {
            $requests[$status] = get_string('status'.$status, 'mod_recommend').': '.$cnt;
        }
        $return->info = join('. ', $requests);
    } else if (has_capability('mod/recommend:request', $cm->context, $user)) {
        $return->info = get_string('norequests', 'mod_recommend');
    }
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $recommend the module instance record
 */
function recommend_user_complete($course, $user, $mod, $recommend) {
    $cm = cm_info::create($mod, $user->id);
    $manager = new mod_recommend_request_manager($cm, $recommend);
    if ($table = $manager->get_requests_table()) {
        echo html_writer::table($table);
    } else if (has_capability('mod/recommend:request', $cm->context, $user)) {
        echo html_writer::div(get_string('norequests', 'mod_recommend'),
                'recommend-complete');
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in recommend activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function recommend_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link recommend_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function recommend_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link recommend_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function recommend_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function recommend_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of recommend?
 *
 * This function returns if a scale is being used by one recommend
 * if it has support for grading and scales.
 *
 * @param int $recommendid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given recommend instance
 */
function recommend_scale_used($recommendid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('recommend', array('id' => $recommendid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of recommend.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any recommend instance
 */
function recommend_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('recommend', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given recommend instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $recommend instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function recommend_grade_item_update(stdClass $recommend, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($recommend->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($recommend->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $recommend->grade;
        $item['grademin']  = 0;
    } else if ($recommend->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$recommend->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/recommend', $recommend->course, 'mod', 'recommend',
            $recommend->id, 0, null, $item);
}

/**
 * Delete grade item for given recommend instance
 *
 * @param stdClass $recommend instance object
 * @return grade_item
 */
function recommend_grade_item_delete($recommend) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/recommend', $recommend->course, 'mod', 'recommend',
            $recommend->id, 0, null, array('deleted' => 1));
}

/**
 * Update recommend grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $recommend instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function recommend_update_grades(stdClass $recommend, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/recommend', $recommend->course, 'mod', 'recommend', $recommend->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function recommend_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for recommend file areas
 *
 * @package mod_recommend
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function recommend_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the recommend file areas
 *
 * @package mod_recommend
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the recommend's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function recommend_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the settings navigation with the recommend settings
 *
 * This function is called when the context for the page is a recommend module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $recommendnode recommend administration node
 */
function recommend_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $recommendnode=null) {
    global $PAGE;
    if (has_capability('mod/recommend:editquestions', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/recommend/edit.php', array('id' => $PAGE->cm->id));
        $recommendnode->add(get_string('editquestions', 'mod_recommend'), $url,
                navigation_node::TYPE_SETTING);
    }
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @param stdClass $commentparam {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function recommend_comment_validate($commentparam) {
    if ($commentparam->commentarea != 'recommend_request') {
        throw new comment_exception('invalidcommentarea');
    }
    return true;

}

/**
 * Running addtional permission check on plugins
 *
 * @param stdClass $params parameters, have attributes commentarea and itemid
 * @return array
 */
function recommend_comment_permissions($params) {
    global $DB, $CFG;
    $canpost = false;
    $canview = false;
    $params = (array)$params;
    if ($params['commentarea'] === 'recommend_request') {
        $request = $DB->get_record('recommend_request', ['id' => $params['itemid']]);
        if ($request) {
            list($course, $cm) = get_course_and_cm_from_instance($request->recommendid, 'recommend');
            $caps = ['mod/recommend:viewdetails', 'mod/recommend:accept'];
            if (has_any_capability($caps, $cm->context) && can_access_course($course) && $cm->uservisible) {
                $canview = $canpost = true;
            }
        }
    }
    return array('post' => $canpost, 'view' => $canview);
}

/**
 * Validate comment data before displaying comments
 *
 * @param stdClass $comment
 * @param stdClass $args
 * @return boolean
 */
function recommend_comment_display($comment, $args) {
    if ($args->commentarea != 'recommend_request') {
        throw new comment_exception('invalidcommentarea');
    }
    return $comment;
}

/**
 * Obtains the automatic completion state for this choice based on any conditions
 * in forum settings.
 *
 * @param stdClass $course Course
 * @param stdClass $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function recommend_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get recommend details.
    $recommend = $DB->get_record('recommend', array('id' => $cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false.
    if ($recommend->requiredrecommend > 0) {
        $statuses = [mod_recommend_request_manager::STATUS_RECOMMENDATION_ACCEPTED];
        if (empty($recommend->completiononlyaccepted)) {
            $statuses[] = mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED;
        }
        list($statussql, $params) = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED);
        $params['recommendid'] = $recommend->id;
        $params['userid'] = $userid;
        $count = $DB->get_field_sql('SELECT COUNT(id) FROM {recommend_request} '
                . 'WHERE recommendid = :recommendid AND userid = :userid '
                . 'AND status ' . $statussql,
                $params);
        return $count >= $recommend->requiredrecommend;
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}