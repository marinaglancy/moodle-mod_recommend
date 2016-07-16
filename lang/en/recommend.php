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
 * English strings for recommend
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['maxrequests'] = 'Maximum requests allowed';
$string['maxrequests_help'] = 'Maximum number of requests the student is allowed to send. '
        . 'It is reasonable to set this value low to encourage student to pre-confirm with '
        . 'the recommending person and prevent them from bulk sending large number of requests. '
        . 'However different circumstances may prevent recommending person from filling out'
        . 'the form and student should be allowed to send more requests than the required'
        . 'number of recommendations';
$string['requiredrecommend'] = 'Required number of recommendations';
$string['requiredrecommend_help'] = 'This setting works best in combination with completion criterion "Required number of recommendations received"';
$string['modulename'] = 'Recommendation request';
$string['modulenameplural'] = 'Recommendation requests';
$string['modulename_help'] = 'Use the Recommendation request module to allow users to request recommendations from anybody including people who are not registered in Moodle';
$string['recommend:addinstance'] = 'Add a new Recommendation request activity';
$string['recommend:request'] = 'Request new recommendation';
$string['recommend:viewdetails'] = 'View the recommendation details (requested and completed)';
$string['recommendfieldset'] = 'Requests options';
$string['recommendname'] = 'Name';
$string['recommendname_help'] = 'Name of the activity as displayed on the course page. Not shown to the recommending person.';
$string['recommend'] = 'recommend';
$string['pluginadministration'] = 'Recommendation request administration';
$string['pluginname'] = 'Recommendation request';
