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
 * Contains class mod_recommend_recommendation
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_recommend_recommendation
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_recommendation {
    /** @var cm_info */
    protected $cm;
    protected $recommend;
    protected $request;
    protected $user;
    protected $questions;

    public function __construct($secret, $id = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/recommend/locallib.php');
        if ($secret) {
            $request = $DB->get_record('recommend_request', ['secret' => $secret]);
            if (!$request) {
                throw new moodle_exception('secret not found'); // TODO string, new exception.
            }
        } else {
            $request = $DB->get_record('recommend_request', ['id' => $id], '*', MUST_EXIST);
        }
        list($course, $cm) = get_course_and_cm_from_instance($request->recommendid, 'recommend');
        $this->cm = $cm;
        $this->recommend = $DB->get_record('recommend', ['id' => $request->recommendid]);
        $this->request = $request;
        $this->user = $DB->get_record('user', ['id' => $request->userid]);
    }

    /**
     *
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    public function get_title() {
        // TODO.
        return 'Recommendation for '.fullname($this->user);
    }

    public function is_submitted() {
        return $this->request->status >= mod_recommend_request_manager::STATUS_REQUEST_SENT;
    }

    public function get_secret() {
        return $this->request->secret;
    }

    public function get_questions() {
        global $DB;
        if ($this->questions === null) {
            $this->questions = $DB->get_records('recommend_question', [], 'sortorder');
        }
        return $this->questions;
    }

    /**
     *
     * @global moodle_database $DB
     * @param stdClass $data
     */
    public function save($data) {
        global $DB;
        $questions = $this->get_questions();

        $answers = [];
        $email = null;
        foreach ($questions as $qid => $question) {
            $rawvalue = isset($data->{'question'.$qid}) ? $data->{'question'.$qid} : null;
            if ($rawvalue === null) {
                $value = null;
            } else if ($question->type === 'radio') {
                $value = reset($rawvalue);
            } else if ($question->type === 'textarea') {
                $value = format_text($rawvalue['text'],
                        $rawvalue['format'],
                        ['filter' => false]);
            } else if ($question->type === 'email') {
                $email = $value = $rawvalue;
            } else {
                $value = $rawvalue;
            }
            $answers[] = [
                'recommendid' => $this->recommend->id,
                'requestid' => $this->request->id,
                'questionid' => $qid,
                'reply' => $value
            ];
        }

        //$DB->delete_records('recommend_reply', ['requestid' => $this->request->id]);
        $now = time();
        $DB->insert_records('recommend_reply', $answers);
        $DB->update_record('recommend_request', ['id' => $this->request->id,
            'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED,
            'timecompleted' => $now]);
        $this->request->status = mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED;
        $this->request->timecompleted = $now;
        \mod_recommend\event\request_completed::create_from_request($this->cm, $this->request)->trigger();
        self::update_completion($this->cm, $this->recommend, $this->request->userid);
        // TODO send email(s).
    }

    public function show_request() {
        global $DB, $OUTPUT, $CFG;
        require_once($CFG->dirroot.'/comment/lib.php');
        // TODO renderer/template.
        echo $OUTPUT->user_picture($this->user);
        echo fullname($this->user).'<br>';
        echo '<b>'.get_string('recommendatorname', 'recommend').':</b> ';
        echo $this->request->name.'<br>';
        echo '<b>'.get_string('email').':</b> ';
        echo $this->request->email.'<br>';
        echo '<b>'.get_string('timerequested', 'recommend').':</b> ';
        echo userdate($this->request->timerequested).'<br>';
        echo '<b>'.get_string('timecompleted', 'recommend').':</b> ';
        echo ($this->request->timecompleted ? userdate($this->request->timecompleted) : '-').'<br>';
        $status = $OUTPUT->pix_icon('status'.$this->request->status, '', 'mod_recommend');
        echo '<b>'.get_string('status', 'recommend').':</b> ';
        echo $status.' '.get_string('status'.$this->request->status, 'recommend').'<br>';

        $replies = $DB->get_records('recommend_reply', ['requestid' => $this->request->id]);
        if ($replies) {
            echo '<hr>';
            $questions = $this->get_questions();
            $data = [];
            foreach ($replies as $reply) {
                $data['question'.$reply->questionid] = $reply->reply;
            }
            $form = new mod_recommend_recommend_form(null,
                ['recommendation' => $this, 'freeze' => true, 'data' => $data]);
            $form->display();

            if ($this->request->status == mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED &&
                    has_capability('mod/recommend:approve', $this->cm->context)) {
                echo '<hr>';
                $urlapprove = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'approverequest', 'requestid' => $this->request->id, 'sesskey' => sesskey()]);
                $urlreject = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'rejectrequest', 'requestid' => $this->request->id, 'sesskey' => sesskey()]);
                echo '<p>'.html_writer::link($urlapprove, 'Approve').'<br>'; // TODO string
                echo html_writer::link($urlreject, 'Reject').'</p>'; // TODO string
            }
        }

        echo '<hr>';
        $commentoptions = new stdClass();
        $commentoptions->area    = 'recommend_request';
        $commentoptions->context = $this->cm->context;
        $commentoptions->itemid  = $this->request->id;
        $commentoptions->component = 'mod_recommend';
        $commentoptions->showcount = true;
        $comment = new comment($commentoptions);
        $comment->init();
        echo $comment->output();
    }

    protected static function update_completion(cm_info $cm, $recommend, $userid) {
        global $CFG;
        if (!$recommend->requiredrecommend) {
            return;
        }

        require_once($CFG->libdir.'/completionlib.php');
        // Update completion state
        $completion = new completion_info($cm->get_course());
        if ($completion->is_enabled($cm) && $recommend->requiredrecommend) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
    }

    protected static function get_request_by_id($requestid) {
        global $DB;
        return $DB->get_record('recommend_request',
            ['id' => $requestid,
                'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED]);

    }

    public static function accept_request(cm_info $cm, $recommend, $requestid) {
        global $DB;
        if (!($request = self::get_request_by_id($requestid))) {
            return false;
        }
        $DB->update_record('recommend_request', ['id' => $requestid,
            'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_ACCEPTED]);
        $request->status = mod_recommend_request_manager::STATUS_RECOMMENDATION_ACCEPTED;
        \mod_recommend\event\request_accepted::create_from_request($cm, $request)->trigger();
        self::update_completion($cm, $recommend, $request->userid);
        return true;
    }

    public static function reject_request(cm_info $cm, $recommend, $requestid) {
        global $DB;
        if (!($request = self::get_request_by_id($requestid))) {
            return false;
        }
        $DB->update_record('recommend_request', ['id' => $requestid,
            'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_REJECTED]);
        $request->status = mod_recommend_request_manager::STATUS_RECOMMENDATION_REJECTED;
        \mod_recommend\event\request_rejected::create_from_request($cm, $request)->trigger();
        self::update_completion($cm, $recommend, $request->userid);
        return true;
    }
}
