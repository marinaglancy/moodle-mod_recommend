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
 * Contains class mod_recommend_request_manager
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_recommend_request_manager
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_request_manager {
    /** @var cm_info */
    protected $cm;
    protected $object;
    protected $requests;

    const STATUS_PENDING = 0;
    const STATUS_REQUEST_SENT = 1;
    const STATUS_REQUEST_REJECTED = 2;
    const STATUS_RECOMMENDATION_COMPLETED = 3;
    const STATUS_RECOMMENDATION_REJECTED = 4;
    const STATUS_RECOMMENDATION_ACCEPTED = 5;

    public function __construct(cm_info $cm, $object) {
        $this->cm = $cm;
        $this->object = $object;
    }

    /**
     *
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    public function get_requests() {
        global $DB, $USER;
        if ($this->requests === null) {
             $this->requests = $DB->get_records('recommend_request',
                     ['recommendid' => $this->object->id, 'userid' => $USER->id]);
        }
        return $this->requests;
    }

    /**
     * Returns the number of requests that this user can add
     * @return int
     */
    public function can_add_request() {
        if (!has_capability('mod/recommend:request', $this->cm->context, null, false)) {
            return 0;
        }
        $canadd = $this->object->maxrequests - count($this->get_requests());
        return max($canadd, 0);
    }

    public function add_requests($data) {
        $canadd = $this->can_add_request();
        for ($i = 1; $i <= $canadd; $i++) {
            if (!empty($data->{'email'.$i})) {
                $email = $data->{'email'.$i};
                $name = $data->{'name'.$i};
                $requests = $this->get_requests();
                foreach ($requests as $request) {
                    if (strtolower($request->email) === strtolower($email)) {
                        // TODO add message about duplicate email.
                        continue 2;
                    }
                }
                $this->add_request($email, $name);
            }
        }
    }

    protected function generate_secret($email, $name) {
        global $USER, $DB;
        while (true) {
            $secret = md5('secret/'.rand(1, 10000).'/'.$USER->id.'/'.$email.'/'.$name);
            if (!$DB->record_exists('recommend_request', ['secret' => $secret])) {
                return $secret;
            }
        }
    }

    protected function add_request($email, $name) {
        global $USER, $DB;
        $this->get_requests();
        $record = (object)array(
            'recommendid' => $this->object->id,
            'userid' => $USER->id,
            'email' => $email,
            'name' => $name,
            'status' => 0, // TODO constant
            'timerequested' => time(),
            'timecompleted' => null,
            'secret' => $this->generate_secret($email, $name)
        );
        $record->id = $DB->insert_record('recommend_request', $record);
        $this->requests[$record->id] = $record;
        \mod_recommend\event\request_created::create_from_request($this->cm, $record)->trigger();
        return $record;
    }

    /**
     *
     * @return \html_table
     */
    public function get_requests_table() {
        global $OUTPUT;
        $requests = $this->get_requests();
        if (!$requests) {
            return null;
        }
        $table = new html_table();
        foreach ($requests as $request) {
            $status = $OUTPUT->pix_icon('status'.$request->status,
                    '', 'mod_recommend');
            $status .= ' '. get_string('status'.$request->status, 'recommend');
            if ($request->status == self::STATUS_PENDING) {
                $deleteurl = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                    'action' => 'deleterequest', 'requestid' => $request->id,
                    'sesskey' => sesskey()]);
                $status .= '<br>'.html_writer::link($deleteurl, get_string('delete'),
                        ['class' => 'deleterequest']);
            }
            $cells = [$request->email, $request->name, $status];
            $table->data[] = new html_table_row($cells);
        }
        return $table;
    }

    public function can_delete_request($requestid) {
        if (!has_capability('mod/recommend:request', $this->cm->context)) {
            return false;
        }
        $requests = $this->get_requests();
        if (!isset($requests[$requestid])) {
            return false;
        }
        return $requests[$requestid]->status == self::STATUS_PENDING;
    }

    protected function get_request_by_id($requestid) {
        global $DB;
        if (isset($this->requests[$requestid])) {
            return $this->requests[$requestid];
        } else {
            return $DB->get_record('recommend_request', ['id' => $requestid]);
        }
    }

    public function delete_request($requestid) {
        global $DB;
        if (!$request = $this->get_request_by_id($requestid)) {
            return false;
        }
        $DB->delete_records('recommend_reply', ['requestid' => $requestid]);
        $DB->delete_records('recommend_request', ['id' => $requestid]);
        \mod_recommend\event\request_deleted::create_from_request($this->cm, $request)->trigger();
        unset($this->requests[$requestid]);
        return true;
    }

    public function accept_request($requestid) {
        global $DB;
        if (!($request = $this->get_request_by_id($requestid)) ||
                        $request->status != self::STATUS_RECOMMENDATION_COMPLETED) {
            return false;
        }
        $DB->update_record('recommend_request', ['id' => $requestid,
            'status' => self::STATUS_RECOMMENDATION_ACCEPTED]);
        $request->status = self::STATUS_RECOMMENDATION_ACCEPTED;
        \mod_recommend\event\request_accepted::create_from_request($this->cm, $request)->trigger();
        return true;
    }

    public function reject_request($requestid) {
        global $DB;
        if (!($request = $this->get_request_by_id($requestid)) ||
                        $request->status != self::STATUS_RECOMMENDATION_COMPLETED) {
            return false;
        }
        $DB->update_record('recommend_request', ['id' => $requestid,
            'status' => self::STATUS_RECOMMENDATION_REJECTED]);
        $request->status = self::STATUS_RECOMMENDATION_REJECTED;
        \mod_recommend\event\request_rejected::create_from_request($this->cm, $request)->trigger();
        return true;
    }

    public function can_view_requests() {
        $caps = ['mod/recommend:viewdetails', 'mod/recommend:approve'];
        return has_any_capability($caps, $this->cm->context);
    }

    public function can_approve_requests() {
        return has_capability('mod/recommend:approve', $this->cm->context);
    }

    /**
     *
     * @global moodle_database $DB
     * @global core_renderer $OUTPUT
     * @return \html_table
     */
    public function get_all_requests_table() {
        global $DB, $OUTPUT;
        $ufields = user_picture::fields('u', null, 'useridalias');
        $sql = "SELECT r.*, $ufields
                FROM {recommend_request} r
                JOIN {user} u ON u.id = r.userid
                WHERE r.recommendid = :recommendid
                ORDER BY r.userid, r.timerequested";
        $params['recommendid'] = $this->object->id;

        $result = $DB->get_records_sql($sql, $params);

        $data = [];
        $maxrequests = 0;
        foreach ($result as $record) {
            if (!isset($data[$record->userid])) {
                $user = user_picture::unalias($record, null, 'userid');
                $data[$record->userid] = ['user' => $user,
                    'fullname' => fullname($user), 'requests' => []];
            }
            $data[$record->userid]['requests'][] = $record;
            $maxrequests = max($maxrequests, count($data[$record->userid]['requests']));
        }

        $enrolledusers = get_enrolled_users($this->cm->context, 'mod/recommend:request');
        foreach ($enrolledusers as $user) {
            if (!isset($data[$user->id])) {
                $data[$user->id] = ['user' => $user,
                    'fullname' => fullname($user), 'requests' => []];
            }
        }

        usort($data, function($a, $b) {
            return strcmp($a['fullname'], $b['fullname']);
        });

        $table = new html_table();
        foreach ($data as $userdata) {
            $cells = [$userdata['fullname']];
            foreach ($userdata['requests'] as $request) {
                $url = new moodle_url('/mod/recommend/view.php', ['id' => $this->cm->id,
                        'requestid' => $request->id, 'action' => 'viewrequest']);
                $status = $OUTPUT->pix_icon('status'.$request->status,
                        get_string('status'.$request->status, 'recommend'), 'mod_recommend');
                $cells[] = html_writer::link($url, $status);
            }
            while (count($cells) < $maxrequests + 1) {
                $cells[] = '';
            }
            $table->data[] = new html_table_row($cells);
        }

        return $table;
    }
}
