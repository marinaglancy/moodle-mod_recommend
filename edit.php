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
 * Edit questions in the module
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace recommend with the name of your module and remove this line.

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // Recommend instance ID.
$action = optional_param('action', null, PARAM_ALPHA);
$questionid = optional_param('questionid', null, PARAM_INT);

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'recommend');
} else if ($r) {
    list($course, $cm) = get_course_and_cm_from_instance($r, 'recommend');
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
if (!has_capability('mod/recommend:editquestions', $PAGE->context)) {
    redirect($cm->url);
}
$recommend = $PAGE->activityrecord;

$questionmanager = new mod_recommend_questions_manager($cm, $recommend);
$editurl = new moodle_url('/mod/recommend/edit.php', ['id' => $cm->id]);

$PAGE->set_url($editurl);
$PAGE->set_title(format_string($recommend->name));
$PAGE->set_heading(format_string($course->fullname));

if ($action) {
    if ($action === 'edit' || $action === 'add') {
        $subtitle = get_string($action === 'edit' ? 'editquestion' : 'addquestion', 'mod_recommend');
        $PAGE->navbar->add($subtitle);
        $options = ['maxfiles' => 0];
        $data = (object)['action' => $action, 'id' => $cm->id, 'questionid' => $questionid];
        if ($action === 'edit') {
            $question = $questionmanager->get_question($questionid);
            $data->type = $question->type;
            $data->question = $question->question;
            $data->questionformat = $question->questionformat;
            $data->addinfo = $question->addinfo;
        } else {
            $data->type = required_param('type', PARAM_ALPHA);
        }
        $editform = new mod_recommend_question_form(null,
            ['data' => $data]);

        if ($editform->is_cancelled()) {
            redirect($editurl);
        } else if ($data = $editform->get_data()) {
            $questionmanager->action($action, $questionid, $data);
            redirect($editurl);
        }
    } else {
        require_sesskey();
        $questionmanager->action($action, $questionid);
        redirect($editurl);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($recommend->name), 2);
if (isset($subtitle)) {
    echo $OUTPUT->heading(format_string($subtitle), 3);
}

if (isset($editform)) {
    $editform->display();
} else {
    $form = new mod_recommend_recommend_form(['recommendation' => $questionmanager],
        mod_recommend_recommend_form::MODE_EDIT);
    $viewurl = new moodle_url('/mod/recommend/view.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');
    $form->display();
    echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');
}

echo $OUTPUT->footer();
