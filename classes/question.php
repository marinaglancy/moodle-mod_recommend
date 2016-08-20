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
 * Contains class mod_recommend_question
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_recommend_question
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_question {

    /** @var mod_recommend_questions_manager */
    protected $qmanager;
    /** @var stdClass */
    protected $record;

    public function __construct(mod_recommend_questions_manager $qmanager, $record) {
        $this->qmanager = $qmanager;
        $this->record = $record;
    }

    public function __get($name) {
        return $this->record->$name;
    }

    public function __set($name, $value) {
        if ($name === 'sortorder') {
            $this->record->$name = $value;
        } else {
            throw new coding_exception('poperty '.$name.' can not be set');
        }
    }

    public function add_to_form($mform, $recommendation, $data, $mode) {
        $question = $this->record;
        $data += ['question'.$question->id => '']; // If question does not have any answer.
        $freeze = $mode == mod_recommend_recommend_form::MODE_REVIEW;

        $label = 'question'.$question->id.'_label';
        $elementlabel = 'question'.$question->id;
        $attrs = ['class' => 'myquestion', 'data-questionid' => $question->id];
        $qtext = format_text($question->question, $question->questionformat);
        if ($question->type === 'label') {
            $mform->addElement('static', $label, '', $qtext);
        } else if ($question->type === 'textarea') {
            $options = ['enable_filemanagement' => false, 'maxfiles' => 0];
            if ($freeze) {
                $mform->addElement('static', $elementlabel, $qtext); // TODO css class
            } else {
                $mform->addElement('editor', $elementlabel, $qtext, null, $options);
            }
        } else if ($question->type === 'textfield') {
            if ($freeze) {
                // TODO nicer
                $mform->addElement('static', $elementlabel, $qtext);
            } else {
                $mform->addElement('text', $elementlabel, $qtext, ['size' => 50] + $attrs);
                $mform->setType($elementlabel, PARAM_NOTAGS);
            }
            if ($question->addinfo === 'email' && method_exists($recommendation, 'get_request_email')) {
                $mform->setDefault($elementlabel, $recommendation->get_request_email());
            }
            if ($question->addinfo === 'name' && method_exists($recommendation, 'get_request_name')) {
                $mform->setDefault($elementlabel, $recommendation->get_request_name());
            }
        } else if ($question->type === 'radio') {
            $lines = preg_split('/\\n/', $question->addinfo, -1, PREG_SPLIT_NO_EMPTY);
            $elements = [];
            foreach ($lines as $line) {
                $parts = preg_split('|/|', $line, 2);
                $optiontext = format_string($parts[1]);
                if ($freeze) {
                    $prefix = ($data['question'.$question->id] == $parts[0]) ? '[X]' : '[ ]';
                    $elements[] = $mform->createElement('static',
                        $elementlabel.'_'.$parts[0], '', $prefix.' '.$optiontext);
                } else {
                    $name = '<span class="accesshide">'.strip_tags($qtext).' </span>'.$optiontext;
                    $elements[] = $mform->createElement('radio',
                        $elementlabel, '', $name, $parts[0]);
                }
            }

            $mform->addElement('group', $label, $qtext, $elements, ['&nbsp;'], false);
        }

    }

    public function duplicate() {
        $data = (object)(array)$this->record;
        unset($data->id);
        $cm = $this->qmanager->get_cm();
        return self::create($cm, $data);
    }

    public function delete() {
        global $DB;
        $DB->delete_records('recommend_reply', ['questionid' => $this->record->id]);
        $DB->delete_records('recommend_question', ['id' => $this->record->id]);
        $cm = $this->qmanager->get_cm();
        mod_recommend\event\question_deleted::create_from_question($cm, $this->record)->trigger();
    }

    public function update($data) {
        global $DB;
        $data->id = $this->record->id;
        unset($data->recommendid);
        unset($data->type); // Can not be updated.
        $DB->update_record('recommend_question', $data);
        foreach ($data as $key => $value) {
            if (property_exists($this->record, $key)) {
                $this->record->$key = $value;
            }
        }
        $cm = $this->qmanager->get_cm();
        mod_recommend\event\question_updated::create_from_question($cm, $this->record)->trigger();
    }

    public static function create(cm_info $cm, $data) {
        global $DB;
        $data->recommendid = $cm->instance;
        $data->id = $DB->insert_record('recommend_question', $data);
        $data = (object)((array)$data + ['question' => null, 'addinfo' => null,
            'questionformat' => FORMAT_MOODLE, 'sortorder' => 0]);
        mod_recommend\event\question_created::create_from_question($cm, $data)->trigger();
        return $data->id;
    }
}
