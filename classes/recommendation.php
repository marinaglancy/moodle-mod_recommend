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
    /** @var stdClass */
    protected $recommend;
    /** @var stdClass */
    protected $request;
    /** @var stdClass */
    protected $user;
    /** @var mod_recommend_questions_manager */
    protected $questionsmanager;

    /**
     * Constructor
     * @param string $secret
     * @param int $id
     */
    public function __construct($secret, $id = null) {
        global $DB, $CFG;
        if ($secret) {
            $request = $DB->get_record('recommend_request', ['secret' => $secret]);
            if (!$request) {
                throw new moodle_exception('error_requestnotfound', 'mod_recommend');
            }
        } else {
            $request = $DB->get_record('recommend_request', ['id' => $id], '*', MUST_EXIST);
        }
        list($course, $cm) = get_course_and_cm_from_instance($request->recommendid, 'recommend');
        $this->cm = $cm;
        $this->recommend = $DB->get_record('recommend', ['id' => $request->recommendid]);
        $this->request = $request;
        $this->user = $DB->get_record('user', ['id' => $request->userid]);
        $this->questionsmanager = new mod_recommend_questions_manager($this->cm, $this->recommend);
    }

    /**
     * Returns the course module
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Display name of the recommendation
     * @return string
     */
    public function get_title() {
        return get_string('recommendationfor', 'mod_recommend', fullname($this->user));
    }

    /**
     * Is this recommendation already submitted?
     * @return bool
     */
    public function is_submitted() {
        return $this->request->status > mod_recommend_request_manager::STATUS_REQUEST_SENT;
    }

    /**
     * Getter for secret
     * @return string
     */
    public function get_secret() {
        return $this->request->secret;
    }

    /**
     * Getter for email
     * @return string
     */
    public function get_request_email() {
        return $this->request->email;
    }

    /**
     * Getter for the recommending person name
     * @return string
     */
    public function get_request_name() {
        return $this->request->name;
    }

    /**
     * Returns the list of questions in the current module
     * @return mod_recommend_question[]
     */
    public function get_questions() {
        return $this->questionsmanager->get_questions();
    }

    /**
     * Saves the form data
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

        $DB->delete_records('recommend_reply', ['requestid' => $this->request->id]);
        $now = time();
        $DB->insert_records('recommend_reply', $answers);
        $DB->update_record('recommend_request', ['id' => $this->request->id,
            'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED,
            'timecompleted' => $now]);
        $this->request->status = mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED;
        $this->request->timecompleted = $now;
        \mod_recommend\event\request_completed::create_from_request($this->cm, $this->request)->trigger();
        self::update_completion($this->cm, $this->recommend, $this->request->userid);
        self::notify($this->request, $this->cm);
    }

    /**
     * Shows a request with or without completed recommendation
     */
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
            $data = [];
            foreach ($replies as $reply) {
                $data['question'.$reply->questionid] = $reply->reply;
            }
            $form = new mod_recommend_recommend_form(
                ['recommendation' => $this, 'data' => $data],
                    mod_recommend_recommend_form::MODE_REVIEW);
            $form->display();

            if ($this->request->status == mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED &&
                    has_capability('mod/recommend:accept', $this->cm->context)) {
                $urlaccept = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'acceptrequest', 'requestid' => $this->request->id, 'sesskey' => sesskey()]);
                $urlreject = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'rejectrequest', 'requestid' => $this->request->id, 'sesskey' => sesskey()]);
                $rejectbutton = new single_button($urlreject, get_string('rejectrecommendation', 'mod_recommend'));
                $rejectbutton->add_confirm_action(get_string('areyousure_reject_recommendation', 'mod_recommend'));
                echo '<hr>' . html_writer::div(
                        $OUTPUT->single_button($urlaccept, get_string('acceptrecommendation', 'mod_recommend')) .
                        $OUTPUT->render($rejectbutton));
            }

            if (has_capability('mod/recommend:delete', $this->cm->context)) {
                $urldelete = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'deleterequest', 'requestid' => $this->request->id, 'sesskey' => sesskey()]);
                $button = new single_button($urldelete, get_string('delete'));
                $button->add_confirm_action(get_string('areyousure_delete_request', 'mod_recommend'));
                echo '<hr>' . html_writer::div($OUTPUT->render($button), 'delete');
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

    /**
     * Updates user completion for the module
     * @param cm_info $cm
     * @param stdClass $recommend
     * @param int $userid
     */
    protected static function update_completion(cm_info $cm, $recommend, $userid) {
        global $CFG;
        if (!$recommend->requiredrecommend) {
            return;
        }

        require_once($CFG->libdir.'/completionlib.php');
        // Update completion state.
        $completion = new completion_info($cm->get_course());
        if ($completion->is_enabled($cm) && $recommend->requiredrecommend) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
    }

    /**
     * Returns a request by id
     * @param int $requestid
     * @return stdClass
     */
    protected static function get_request_by_id($requestid) {
        global $DB;
        return $DB->get_record('recommend_request',
            ['id' => $requestid,
                'status' => mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED]);

    }

    /**
     * Accept a request
     *
     * @param cm_info $cm
     * @param stdClass $recommend
     * @param int $requestid
     * @return bool
     */
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
        self::notify($request, $cm);
        return true;
    }

    /**
     * Reject a request
     * @param cm_info $cm
     * @param stdClass $recommend
     * @param int $requestid
     * @return bool
     */
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
        self::notify($request, $cm);
        return true;
    }

    /**
     * Notify student/teachers about the request status change.
     *
     * @param stdClass $request
     * @param cm_info $cm course module, note that it may be initialised for the current
     *     user instead of affected user. Do not call uservisible!
     */
    public static function notify($request, $cm) {

        $context = $cm->context;
        $contextname = $cm->get_formatted_name();
        $course = $cm->get_course();
        $formatoptions = ['context' => $context->get_course_context()];
        $site = get_site();

        $user = core_user::get_user($request->userid);
        $a = (object)array(
            'name' => $request->name,
            'email' => $request->email,
            'participant' => fullname($user),
            'recipient' => fullname($user),
            'status' => get_string('status' . $request->status, 'mod_recommend'),
            'modulename' => $contextname,
            'courseshortname' => format_string($course->shortname, true, $formatoptions),
            'coursefullname' => format_string($course->fullname, true, $formatoptions),
            'site' => format_string($site->fullname, true, ['context' => context_system::instance()]),
            'admin' => generate_email_signoff(),
            'link' => $cm->url->out(false),
        );

        // Send notification to student that status has changed.
        $subject = get_string('notificationstatuschanged_subject', 'mod_recommend', $a);
        $message = text_to_html(get_string('notificationstatuschanged_body', 'mod_recommend', $a), null, false);
        $smallmessage = get_string('notificationstatuschanged_short', 'mod_recommend', $a);

        $eventdata = new \core\message\message();
        $eventdata->component           = 'mod_recommend';
        $eventdata->name                = 'statuschanged';
        $eventdata->userfrom            = core_user::get_noreply_user();
        $eventdata->userto              = $user;
        $eventdata->subject             = $subject;
        $eventdata->fullmessage         = $message;
        $eventdata->fullmessageformat   = FORMAT_HTML;
        $eventdata->fullmessagehtml     = $message;
        $eventdata->notification        = 1;
        $eventdata->smallmessage        = $smallmessage;
        $eventdata->contexturl          = $cm->url->out(false);
        $eventdata->contexturlname      = $contextname;
        $mailresult = message_send($eventdata);

        // Send notification to teachers about completed recommendation.

        if ($request->status != mod_recommend_request_manager::STATUS_RECOMMENDATION_COMPLETED) {
            // Teachers only need notification when recommendation is completed.
            return;
        }

        $recipients = get_enrolled_users($context, 'mod/recommend:accept');
        if (!$recipients) {
            return;
        }

        $viewurl = new moodle_url('/mod/recommend/view.php', ['id' => $cm->id,
            'requestid' => $request->id, 'action' => 'viewrequest']);
        $a->link = $viewurl->out(false);

        foreach ($recipients as $recipient) {
            $a->recipient = fullname($recipient);

            $subject = get_string('notificationcompleted_subject', 'mod_recommend', $a);
            $message = text_to_html(get_string('notificationcompleted_body', 'mod_recommend', $a), null, false);
            $smallmessage = get_string('notificationcompleted_short', 'mod_recommend', $a);

            $eventdata = new \core\message\message();
            $eventdata->component           = 'mod_recommend';
            $eventdata->name                = 'completed';
            $eventdata->userfrom            = core_user::get_noreply_user();
            $eventdata->userto              = $recipient;
            $eventdata->subject             = $subject;
            $eventdata->fullmessage         = $message;
            $eventdata->fullmessageformat   = FORMAT_HTML;
            $eventdata->fullmessagehtml     = $message;
            $eventdata->notification        = 1;
            $eventdata->smallmessage        = $smallmessage;
            $eventdata->contexturl          = $viewurl->out(false);
            $eventdata->contexturlname      = $contextname;
            $mailresult = message_send($eventdata);
        }
    }
}
