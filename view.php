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
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
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

$title = $cm->get_formatted_name();

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
        \core\notification::add(get_string('requestscreated', 'mod_recommend'),
                core\output\notification::NOTIFY_SUCCESS);
        redirect($viewurl);
    }
    $title = get_string('addrequest', 'mod_recommend');
    $PAGE->navbar->add($title);
} else if ($action === 'resendrequest' && ($request = $manager->validate_request($requestid))) {
    if ($manager->can_resend_request($requestid)) {
        require_sesskey();
        $manager->email_request($request, $recommend);
        \core\notification::add(get_string('requestsent', 'mod_recommend'),
                core\output\notification::NOTIFY_SUCCESS);
    }
    if (optional_param('returnto', '', PARAM_ALPHA) === 'viewrequest') {
        redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']));
    } else {
        redirect($viewurl);
    }
} else if ($action === 'deleterequest' && $manager->validate_request($requestid)) {
    $message = '';
    $status = \core\output\notification::NOTIFY_SUCCESS;
    if ($manager->can_delete_request($requestid)) {
        require_sesskey();
        if ($manager->delete_request($requestid)) {
            $message = get_string('requestdeleted', 'mod_recommend');
        }
    } else {
        $message = get_string('error_cannotdeleterequest', 'mod_recommend');
        $status = \core\output\notification::NOTIFY_ERROR;
    }
    redirect($viewurl, $message, 3, $status);
} else if ($action === 'acceptrequest' && $manager->validate_request($requestid) &&
        $manager->can_accept_requests() && confirm_sesskey()) {
    $message = '';
    if ($manager->accept_request($requestid)) {
        $message = get_string('recommendationaccepted', 'mod_recommend');
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']),
            $message, 3, \core\output\notification::NOTIFY_SUCCESS);
} else if ($action === 'rejectrequest' && $manager->validate_request($requestid) &&
        $manager->can_accept_requests() && confirm_sesskey()) {
    $message = '';
    if ($manager->reject_request($requestid)) {
        $message = get_string('recommendationrejected', 'mod_recommend');
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']),
            $message, 3, \core\output\notification::NOTIFY_SUCCESS);
} else if ($action === 'viewrequest' && $manager->validate_request($requestid) &&
        $manager->can_view_requests()) {
    $request = new mod_recommend_recommendation(null, $requestid);
    $title = $request->get_title();
    $PAGE->navbar->add($title);
} else if ($action) {
    redirect($viewurl);
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

echo $OUTPUT->heading($title);

// Conditions to show the intro can change to look for own settings or whatever.
if ($recommend->intro && !$action) {
    echo $OUTPUT->box(format_module_intro('recommend', $recommend, $cm->id), 'generalbox mod_introbox', 'recommendintro');
}
if (!$action) {
    if (!has_capability('mod/recommend:editquestions', $PAGE->context)) {
        $previewurl = new moodle_url('/mod/recommend/preview.php', ['id' => $cm->id]);
        echo $OUTPUT->single_button($previewurl, get_string('preview', 'mod_recommend'), 'get');
    } else {
        $editurl = new moodle_url('/mod/recommend/edit.php', ['id' => $cm->id]);
        echo $OUTPUT->single_button($editurl, get_string('editquestions', 'mod_recommend'), 'get');
    }
}

if ($action === 'addrequest') {
    $form->display();
} else if ($action === 'viewrequest') {
    $request->show_request($requestid);
} else {
    if ($table = $manager->get_requests_table()) {
        echo $OUTPUT->heading(get_string('yourrecommendations', 'mod_recommend'), 3);
        echo html_writer::table($table);
    }
    if ($manager->can_add_request()) {
        $addrequesturl = new moodle_url($viewurl, ['action' => 'addrequest']);
        echo html_writer::div(html_writer::link($addrequesturl,
                get_string('addrequest', 'recommend')), 'addrequest');
    }
    if ($manager->can_view_requests()) {
        $table = $manager->get_all_requests_table();
        echo $OUTPUT->heading(get_string('allrequests', 'mod_recommend'), 3);
        echo html_writer::table($table);
    }
}

// Finish the page.
echo $OUTPUT->footer();
