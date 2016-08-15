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

$string['acceptrecommendation'] = 'Accept';
$string['addrequest'] = 'Request recommendation';
$string['allrequests'] = 'All requests';
$string['completiononlyaccepted'] = 'Count only accepted recommendations';
$string['completionrequired'] = 'Minimum number of completed recommendations: ';
$string['email_request_subject'] = '';
$string['error_cannotdeleterequest'] = 'Sorry, this request can not be deleted';
$string['error_emailduplicated'] = 'Duplicate e-mail address';
$string['error_emailmissing'] = 'E-mail for this recommendation is not specified';
$string['error_emailnotvalid'] = 'E-mail address is not valid';
$string['error_emailused'] = 'Request to this e-mail has already been sent';
$string['error_recommendationsubmitted'] = 'This recommendation is already submitted';
$string['error_requestnotfound'] = 'Sorry we could not find this recommendation requst';
$string['eventrequestaccepted'] = 'Recommendation accepted';
$string['eventrequestcompleted'] = 'Recommendation completed';
$string['eventrequestcreated'] = 'Recommendation request created';
$string['eventrequestdeclined'] = 'Recommendation request declined';
$string['eventrequestdeleted'] = 'Recommendation request deleted';
$string['eventrequestrejected'] = 'Recommendation rejected';
$string['maxrequests'] = 'Maximum requests allowed';
$string['maxrequests_help'] = 'Maximum number of requests a participant is allowed to send. '
        . 'It is reasonable to set this value low to encourage participants to pre-confirm with '
        . 'the recommending person and prevent them from bulk sending large number of requests. '
        . 'However different circumstances may prevent recommending person from filling out '
        . 'the form and participant should be allowed to send more requests than the required '
        . 'number of recommendations';
$string['modulename'] = 'Recommendation request';
$string['modulename_help'] = 'Use the Recommendation request module to allow users to request recommendations from anybody including people who are not registered in Moodle';
$string['modulenameplural'] = 'Recommendation requests';
$string['pluginadministration'] = 'Recommendation request administration';
$string['pluginname'] = 'Recommendation request';
$string['recommend'] = 'recommend';
$string['recommend:addinstance'] = 'Add a new Recommendation request activity';
$string['recommend:request'] = 'Request new recommendation';
$string['recommend:viewdetails'] = 'View the recommendation details (requested and completed)';
$string['recommendationaccepted'] = 'Recommendation accepted';
$string['recommendationfor'] = 'Recommendation for {$a}';
$string['recommendationrejected'] = 'Recommendation rejected';
$string['recommendationtitle'] = 'Recommendation {$a}';
$string['recommendatorname'] = 'Name of the recommending person';
$string['recommendname'] = 'Name';
$string['recommendname_help'] = 'Name of the activity as displayed on the course page. Not shown to the recommending person.';
$string['rejectrecommendation'] = 'Reject';
$string['requestdeleted'] = 'Request was deleted';
$string['requestemailsubject'] = 'Request e-mail subject';
$string['requestemailtemplate'] = 'Request e-mail template';
$string['requestinstructions'] = 'Please use this form to send requests to up to {$a} people you would like to recommend you. They will be able to fill the recommendation form online.';
$string['requestssettings'] = 'Requests settings';
$string['requesttemplatebody'] = 'Dear {NAME}

{PARTICIPANT} has asked you for a recommendation on {SITE}.
To fill the recommendation form online please follow the link:
{LINK}

If you need help, please contact the site administrator,
{ADMIN}
';
$string['requesttemplatesubject'] = 'Recommendation request from {SITE}';
$string['requiredrecommendgroup'] = 'Recommendations received';
$string['requiredrecommendgroup_help'] = 'If the setting "Count only accepted recommendations" is unchecked, completed recommendations that have not yet been reviewed by the teacher will be counted. However if later the completed recommendation becomes rejected by the teacher the completed activity may become incomplete again.';
$string['status'] = 'Status';
$string['status0'] = 'Scheduled';
$string['status1'] = 'Recommendation request sent';
$string['status2'] = 'Recommendation request declined';
$string['status3'] = 'Recommendation completed';
$string['status4'] = 'Recommendation rejected';
$string['status5'] = 'Recommendation accepted';
$string['taskname'] = 'Send scheduled recommendation requests';
$string['thanksforrecommendation'] = 'Thank you, your recommendation has been processed.';
$string['timecompleted'] = 'Completed';
$string['timerequested'] = 'Requested';
$string['yourrecommendations'] = 'Your recommendations';
