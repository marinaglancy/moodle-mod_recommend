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
 * Contains class mod_recommend\event\request_updated
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_recommend\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_recommend\event\request_updated
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class request_updated extends \core\event\base {

    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'recommend_request';
    }

    /**
     * Method for quick creating from object
     * @param \cm_info $cm
     * @param \stdClass $request
     * @return self
     */
    public static function create_from_request($cm, $request) {
        $event = static::create(array(
            'objectid' => $request->id,
            'context' => $cm->context,
            'relateduserid' => $request->userid,
        ));
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('recommend_request', $request);
        return $event;
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/mod/recommend/view.php",
                array('id' => $this->contextinstanceid, 'action' => 'viewrequest',
                    'requestid' => $this->objectid));
    }
}
