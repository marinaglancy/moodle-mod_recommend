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
 * Contains class mod_recommend_questions_manager
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_recommend_questions_manager
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_questions_manager {

    /** @var cm_info */
    protected $cm;
    /** @var stdClass */
    protected $recommend;
    /** @var mod_recommend_question[] */
    protected $questions;

    /**
     * Constructor
     *
     * @param cm_info $cm
     * @param stdClass $recommend
     */
    public function __construct(cm_info $cm, $recommend) {
        $this->cm = $cm;
        $this->recommend = $recommend;
    }

    /**
     * Returns list of names of available question types
     * @return string[]
     */
    public static function get_types() {
        $types = ['label', 'radio', 'textarea', 'textfield'];
        $result = [];
        foreach ($types as $type) {
            $result[$type] = get_string('type' . $type, 'mod_recommend');
        }
        return $result;
    }

    /**
     * Returns the course module object
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Returns the list of questions in the current module
     * @return mod_recommend_question[]
     */
    public function get_questions() {
        global $DB;
        if ($this->questions === null) {
            $records = $DB->get_records('recommend_question',
                ['recommendid' => $this->recommend->id], 'sortorder, id');
            $this->questions = [];
            $cnt = 0;
            foreach ($records as $record) {
                if ($record->sortorder != $cnt) {
                    $this->set_sortorder($record->id, $cnt);
                    $record->sortorder = $cnt;
                }
                $this->questions[$record->id] = new mod_recommend_question($this, $record);
                $cnt++;
            }
        }
        return $this->questions;
    }

    /**
     * Returns one question from the current module
     * @param int $id
     * @param int $strictness
     * @return mod_recommend_question
     */
    public function get_question($id, $strictness = MUST_EXIST) {
        $questions = $this->get_questions();
        if (isset($questions[$id])) {
            return $questions[$id];
        }
        if ($strictness == MUST_EXIST) {
            throw new moodle_exception(get_string('error_questionnotfound', 'mod_recommend'));
        }
        return null;
    }

    /**
     * Returns number of questions in the current module
     * @return int
     */
    public function get_questions_count() {
        return count($questions = $this->get_questions());
    }

    /**
     * Activity object (record from table recommend)
     * @return stdClass
     */
    public function get_recommend() {
        return $this->recommend;
    }

    /**
     * Changes sort order of a question, no events fired
     * @param int $questionid
     * @param int $sortorder
     */
    protected function set_sortorder($questionid, $sortorder) {
        global $DB;
        $DB->update_record('recommend_question', ['id' => $questionid, 'sortorder' => $sortorder]);
        if (isset($this->questions[$questionid])) {
            $this->questions[$questionid]->sortorder = $sortorder;
        }
    }

    /**
     * Performs an action on the question
     * @param string $action action - add, edit, moveup, movedown, duplicate, delete
     * @param int $questionid
     * @param null|stdClass $data only for actions 'add' and 'edit'
     */
    public function action($action, $questionid, $data = null) {
        if ($action === 'add' && !$questionid) {
            $question = null;
        } else {
            $question = $this->get_question($questionid);
        }
        if ($data) {
            if (isset($data->question_editor)) {
                $data = file_postupdate_standard_editor($data, 'question',
                        self::editor_options($data), $this->cm->context);
            }
            $record = array_intersect_key((array)$data,
                ['type' => true, 'question' => true, 'questionformat' => true, 'addinfo' => true]);
            $data = (object)$record;
        }

        if ($action === 'moveup' && $question->sortorder > 0) {
            $indexes = array_keys($this->get_questions());
            $this->set_sortorder($indexes[$question->sortorder - 1], $question->sortorder);
            $this->set_sortorder($indexes[$question->sortorder], $question->sortorder - 1);
        }
        if ($action === 'movedown' && $question->sortorder < $this->get_questions_count() - 1) {
            $indexes = array_keys($this->get_questions());
            $this->set_sortorder($indexes[$question->sortorder + 1], $question->sortorder);
            $this->set_sortorder($indexes[$question->sortorder], $question->sortorder + 1);
        }
        if ($action === 'duplicate') {
            $question->duplicate();
            $this->questions = null;
        }
        if ($action === 'delete') {
            $question->delete();
            $this->questions = null;
        }
        if ($action === 'edit') {
            $question->update($data);
            $this->questions = null;
        }
        if ($action === 'add') {
            if ($question) {
                $data->sortorder = $question->sortorder - 1;
            } else {
                $data->sortorder = $this->get_questions_count();
            }
            mod_recommend_question::create($this->cm, $data);
            $this->questions = null;
        }
    }

    /**
     * Options for the form editor element
     * @param stdClass $data
     * @return array
     */
    public static function editor_options($data) {
        // Maybe we add files support later.
        return ['maxfiles' => 0];
    }
}
