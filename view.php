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
 * Prints a particular instance of recommend
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace recommend with the name of your module and remove this line.

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // Recommend instance ID.
$action = optional_param('action', null, PARAM_ALPHA);
$requestid = optional_param('requestid', null, PARAM_INT);

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'recommend');
} else if ($r) {
    list($course, $cm) = get_course_and_cm_from_instance($r, 'recommend');
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$recommend = $PAGE->activityrecord;

$manager = new mod_recommend_request_manager($cm, $recommend);
$viewurl = new moodle_url('/mod/recommend/view.php', ['id' => $cm->id]);

$statussuccess = $statuserror = null;
if (class_exists('core\output\notification')) {
    // Notification status will not work in Moodle 2.7.
    $statussuccess = \core\output\notification::NOTIFY_SUCCESS;
    $statuserror = \core\output\notification::NOTIFY_ERROR;
}

if ($action === null) {
    \mod_recommend\event\course_module_viewed::create_from_cm($cm, $course, $recommend)->trigger();
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
} else if ($action === 'addrequest' && $manager->can_add_request()) {
    $form = new mod_recommend_add_request_form(null, ['manager' => $manager]);
    if ($form->is_cancelled()) {
        redirect($viewurl);
    } else if ($data = $form->get_data()) {
        $manager->add_requests($data);
        // TODO add success message.
        redirect($viewurl);
    }
} else if ($action === 'deleterequest' && $requestid) {
    $message = '';
    $status = $statussuccess;
    if ($manager->can_delete_request($requestid)) {
        require_sesskey();
        $manager->delete_request($requestid);
        $message = 'Request was deleted'; // TODO
    } else {
        $message = 'Sorry, this request can not be deleted'; // TODO
        $status = $statuserror;
    }
    redirect($viewurl, $message, 3, $status);
} else if ($action === 'acceptrequest' && $requestid &&
        $manager->can_accept_requests() && confirm_sesskey()) {
    $message = '';
    if ($manager->accept_request($requestid)) {
        $message = 'Recommendation accepted'; //TODO
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']),
            $message, 3, $statussuccess);
} else if ($action === 'rejectrequest' && $requestid &&
        $manager->can_accept_requests() && confirm_sesskey()) {
    $message = '';
    if ($manager->reject_request($requestid)) {
        $message = 'Recommendation rejected'; // TODO
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']),
            $message, 3, $statussuccess);
}

// Print the page header.

$urlparams = ['id' => $cm->id];
if ($action) {
    $urlparams['action'] = $action;
}
if ($requestid) {
    $urlparams['requestid'] = $requestid;
}
$PAGE->set_url('/mod/recommend/view.php', $urlparams);
$PAGE->set_title(format_string($recommend->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($recommend->name));

// Conditions to show the intro can change to look for own settings or whatever.
if ($recommend->intro && !$action) {
    echo $OUTPUT->box(format_module_intro('recommend', $recommend, $cm->id), 'generalbox mod_introbox', 'recommendintro');
}

if ($action === 'addrequest') {
    $form->display();
} else if ($action === 'viewrequest') {
    if ($manager->can_view_requests()) {
        $request = new mod_recommend_recommendation(null, $requestid);
        $request->show_request($requestid);
    }
} else {
    if ($table = $manager->get_requests_table()) {
        echo $OUTPUT->heading('Your recommendations', 3); // TODO lang string
        echo html_writer::table($table);
    }
    if ($manager->can_add_request()) {
        $addrequesturl = new moodle_url($viewurl, ['action' => 'addrequest']);
        echo html_writer::div(html_writer::link($addrequesturl,
                get_string('addrequest', 'recommend')), 'addrequest');
    }
    if ($manager->can_view_requests()) {
        $table = $manager->get_all_requests_table();
        echo $OUTPUT->heading('All requests', 3); // TODO lang string
        echo html_writer::table($table);
    }
}

// Finish the page.
echo $OUTPUT->footer();
