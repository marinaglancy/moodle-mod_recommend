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
 * Contains class mod_recommend_recommend_form
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class mod_recommend_recommend_form
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_recommend_form extends moodleform {
    public function definition() {
        /** @var mod_recommend_recommendation */
        $recommendation = $this->_customdata['recommendation'];
        $freeze = !empty($this->_customdata['freeze']);
        $data = isset($this->_customdata['data']) ? $this->_customdata['data'] : [];

        $mform = $this->_form;

        if (!$freeze) {
            $mform->addElement('hidden', 'secret', $recommendation->get_secret());
            $mform->setType('secret', PARAM_RAW);
        }

        $questions = $recommendation->get_questions();

        foreach ($questions as $question) {
            $label = 'question'.$question->id.'_label';
            $elementlabel = 'question'.$question->id;
            $qtext = format_text($question->question, $question->questionformat);
            if ($question->type === 'label') {
                // TODO make it wide.
                $mform->addElement('static', $label, '', $qtext);
            } else if ($question->type === 'textarea') {
                if (core_text::strlen($qtext)) {
                    $mform->addElement('static', $label, '', $qtext);
                }
                $options = ['enable_filemanagement' => false, 'maxfiles' => 0];
                if ($freeze) {
                    $mform->addElement('static', $elementlabel, ''); // TODO css class
                } else {
                    $mform->addElement('editor', $elementlabel, '', null, $options);
                }
            } else if ($question->type === 'textfield' || $question->type === 'email') {
                if ($freeze) {
                    // TODO nicer
                    $mform->addElement('static', $elementlabel, $qtext);
                } else {
                    $mform->addElement('text', $elementlabel, $qtext);
                    $mform->setType($elementlabel, PARAM_NOTAGS);
                }
            } else if ($question->type === 'radio') {
                $lines = preg_split('/\\n/', $question->addinfo, -1, PREG_SPLIT_NO_EMPTY);
                $elements = [];
                foreach ($lines as $line) {
                    $parts = preg_split('|/|', $line, 2);
                    if ($freeze) {
                        $prefix = ($data['question'.$question->id] == $parts[0]) ? '[X]' : '[ ]';
                        $elements[] = $mform->createElement('static',
                            $elementlabel.'_'.$parts[0], '', $prefix.' '.$parts[1]);
                    } else {
                        $elements[] = $mform->createElement('radio',
                            $elementlabel.'_'.$parts[0], '', $parts[1], $parts[0]);
                    }
                }

                $mform->addElement('group', $elementlabel, $qtext, $elements);
            }
        }

        if (!$freeze) {
            $this->add_action_buttons(false);
        }

        if ($data) {
            $this->set_data($data);
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $recommendation = $this->_customdata['recommendation'];
        $questions = $recommendation->get_questions();
        foreach ($questions as $qid => $question) {
            if ($question->type === 'email') {
                $value = $data['question'.$qid];
                if (clean_param($value, PARAM_EMAIL) !== $value) {
                    $errors['question'.$qid] = 'Invalid e-mail'; // TODO string.
                }
            }
        }

        return $errors;
    }
}
