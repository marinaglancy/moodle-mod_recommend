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
 * Allows a person to fill recommendation online without registering in moodle
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace recommend with the name of your module and remove this line.

require_once(__DIR__.'/../../config.php');

$secret = required_param('s', PARAM_RAW);
$recommendation = new mod_recommend_recommendation($secret);
$baseurl = new moodle_url('/mod/recommend/recommend.php', ['s' => $secret]);

// Print the page header.
$PAGE->set_context(context_system::instance());
$PAGE->set_url($baseurl);
$PAGE->set_title($recommendation->get_title());
$PAGE->navbar->add($recommendation->get_title());

$form = new mod_recommend_recommend_form(['recommendation' => $recommendation]);
if ($data = $form->get_data()) {
    $recommendation->save($data);
    echo $OUTPUT->header();
    echo "<p>".get_string('thanksforrecommendation', 'mod_recommend')."</p>";
    echo $OUTPUT->footer();
    exit;
} else if ($recommendation->is_submitted()) {
    echo $OUTPUT->header();
    echo '<p>'.get_string('error_recommendationsubmitted', 'mod_recommend').'</p>';
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading($recommendation->get_title());
$form->display();
echo $OUTPUT->footer();
