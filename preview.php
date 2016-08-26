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
 * Allows student to preview the questions in the recommendation
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // Recommend instance ID.

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'recommend');
} else if ($r) {
    list($course, $cm) = get_course_and_cm_from_instance($r, 'recommend');
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$recommend = $PAGE->activityrecord;

$qmanager = new mod_recommend_questions_manager($cm, $recommend);
$previewurl = new moodle_url('/mod/recommend/preview.php', ['id' => $cm->id]);

$PAGE->set_url($previewurl);
$PAGE->set_title(format_string($recommend->name));
$PAGE->set_heading(format_string($course->fullname));

$form = new mod_recommend_recommend_form(['recommendation' => $qmanager],
        mod_recommend_recommend_form::MODE_PREVIEW);
$PAGE->navbar->add(get_string('preview', 'mod_recommend'));

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($recommend->name));

$viewurl = new moodle_url('/mod/recommend/view.php', ['id' => $cm->id]);

echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');
$form->display();
echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');

// Finish the page.
echo $OUTPUT->footer();
