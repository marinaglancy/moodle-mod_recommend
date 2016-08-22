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

    /** @var int */
    protected $mode = 0;

    /** Filling the recommendation form */
    const MODE_FILL = 0;
    /** Reviewing the completed recommendation form */
    const MODE_REVIEW = 1;
    /** Editing questions for the recommendation form */
    const MODE_EDIT = 2;
    /** Previewing the questions in the recommendation form */
    const MODE_PREVIEW = 3;

    /**
     * Constructor
     * @param array $customdata
     * @param int $mode
     */
    public function __construct($customdata = null, $mode = 0) {
        global $PAGE;
        $attributes = ['class' => 'mod-recommend-recommendation'];
        if ($mode == self::MODE_EDIT) {
            $attributes['class'] .= ' editing';
            $PAGE->requires->js_call_amd('mod_recommend/edit', 'setup',
                ['types' => mod_recommend_questions_manager::get_types()]);
        }
        $this->mode = $mode;
        parent::__construct(null, $customdata, 'post', '', $attributes);
    }

    /**
     * Form definition
     */
    public function definition() {

        $mform = $this->_form;

        $recommendation = $this->_customdata['recommendation'];
        $data = isset($this->_customdata['data']) ? $this->_customdata['data'] : [];

        if ($this->mode == self::MODE_FILL) {
            $mform->addElement('hidden', 's', $recommendation->get_secret());
            $mform->setType('s', PARAM_RAW);
        }

        $questions = $recommendation->get_questions();

        foreach ($questions as $question) {
            $question->add_to_form($mform, $recommendation, $data, $this->mode);
        }

        if ($this->mode == self::MODE_FILL) {
            $this->add_action_buttons(false);
        }

        if ($data) {
            $this->set_data($data);
        }
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $recommendation = $this->_customdata['recommendation'];
        $questions = $recommendation->get_questions();
        foreach ($questions as $qid => $question) {
            if ($question->type === 'email') {
                $value = $data['question'.$qid];
                if (clean_param($value, PARAM_EMAIL) !== $value) {
                    $errors['question'.$qid] = get_string('error_emailnotvalid', 'mod_recommend');
                }
            }
        }

        return $errors;
    }
}
