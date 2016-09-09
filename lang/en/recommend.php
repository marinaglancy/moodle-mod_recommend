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
$string['addquestion'] = 'Add question';
$string['addrequest'] = 'Request recommendation';
$string['allrequests'] = 'All requests';
$string['areyousure_delete_request'] = 'Are you sure you want to completely delete this recommendation request?';
$string['areyousure_reject_recommendation'] = 'Are you sure you want to reject this recommendation?';
$string['completiononlyaccepted'] = 'Count only accepted recommendations';
$string['completionrequired'] = 'Minimum number of completed recommendations: ';
$string['editquestion'] = 'Edit question';
$string['editquestions'] = 'Edit questions';
$string['error_cannotdeleterequest'] = 'Sorry, this request can not be deleted';
$string['error_emailduplicated'] = 'Duplicate e-mail address';
$string['error_emailmissing'] = 'E-mail for this recommendation is not specified';
$string['error_emailnotvalid'] = 'E-mail address is not valid';
$string['error_emailused'] = 'Request to this e-mail has already been sent';
$string['error_questionnotfound'] = 'Question not found';
$string['error_recommendationsubmitted'] = 'This recommendation is already submitted';
$string['error_requestnotfound'] = 'Sorry we could not find this recommendation request';
$string['eventquestioncreated'] = 'Recommendation question created';
$string['eventquestiondeleted'] = 'Recommendation question deleted';
$string['eventquestionupdated'] = 'Recommendation question updated';
$string['eventrequestaccepted'] = 'Recommendation accepted';
$string['eventrequestcompleted'] = 'Recommendation completed';
$string['eventrequestcreated'] = 'Recommendation request created';
$string['eventrequestdeclined'] = 'Recommendation request declined';
$string['eventrequestdeleted'] = 'Recommendation request deleted';
$string['eventrequestrejected'] = 'Recommendation rejected';
$string['labelcontents'] = 'Contents';
$string['maxrequests'] = 'Maximum requests allowed';
$string['maxrequests_help'] = 'Maximum number of requests a participant is allowed to send.
 It is reasonable to set this value low to encourage participants to pre-confirm with
 the recommending person and prevent them from bulk sending large number of requests.
 However different circumstances may prevent recommending person from filling out
 the form and participant should be allowed to send more requests than the required
 number of recommendations';
$string['messageprovider:completed'] = 'Completed recommendation notification for graders';
$string['messageprovider:statuschanged'] = 'Recommendation request status change notification';
$string['modulename'] = 'Recommendation request';
$string['modulename_help'] = 'This module allows participants to request recommendations. Recommending person does not need to register on the site. The grader is able to view recommendations, accept or decline them.';
$string['modulenameplural'] = 'Recommendation requests';
$string['norequests'] = 'No recommendation requests created';
$string['notificationcompleted_body'] = 'Dear {$a->recipient},

New recommendation was completed on {$a->site} and is now waiting to be accepted.

Participant: {$a->participant}
Name of the recommending person: {$a->name}

To view recommendation follow the link:
{$a->link}

If you need help, please contact the site administrator,
{$a->admin}
';
$string['notificationcompleted_short'] = 'New recommendation';
$string['notificationcompleted_subject'] = '{$a->courseshortname}: {$a->modulename}: New recommendation';
$string['notificationstatuschanged_body'] = 'Dear {$a->recipient},

This is to notify you about the change in the status of the recommendation you requested.
Name of the recommending person: {$a->name}
E-mail: {$a->email}
New status: <b>{$a->status}</b>

To view all your recommendation requests follow the link:
{$a->link}

If you need help, please contact the site administrator,
{$a->admin}
';
$string['notificationstatuschanged_short'] = '{$a->courseshortname}: {$a->modulename}: Your recommendation request status has changed';
$string['notificationstatuschanged_subject'] = 'Your recommendation request status has changed';
$string['options'] = 'Options';
$string['options_help'] = 'Specify one option per line, prefix each line with the weight and / sign. All weights must be different, for example:<br><pre>1/Bad<br>3/Good<br>5/Excellent</pre>';
$string['pluginadministration'] = 'Recommendation request administration';
$string['pluginname'] = 'Recommendation request';
$string['prefillwith'] = 'Prefill with:';
$string['preview'] = 'Preview recommendation';
$string['question'] = 'Question';
$string['questiontype'] = 'Type';
$string['recommend:accept'] = 'Accept and reject completed recommendations';
$string['recommend:addinstance'] = 'Add a new Recommendation request activity';
$string['recommend:delete'] = 'Delete any request or recommendation';
$string['recommend:editquestions'] = 'Edit questions in the recommendation';
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
$string['requestinstructions'] = 'Use this form to send requests to up to {$a} people who would be able to recommend you.
Each recommending person will receive an e-mail with a link to an online recommendation form.
Be careful when filling out this form, after recommendation request is sent you will not be able to make any changes.';
$string['requestinstructionsmult'] = 'You do not need to send all request at once, you can come back at send additional requests at any time.';
$string['requestscreated'] = 'Recommendaiton request(s) created.';
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
$string['selectquestiontype'] = 'Select question type';
$string['status'] = 'Status';
$string['status0'] = 'Scheduled';
$string['status1'] = 'Recommendation request sent';
$string['status2'] = 'Recommendation request declined';
$string['status3'] = 'Recommendation completed';
$string['status4'] = 'Recommendation rejected';
$string['status5'] = 'Recommendation accepted';
$string['suredeletequestion'] = 'Are you sure you want to delete this question? All replies to this question in the existing recommendations will also be deleted';
$string['taskname'] = 'Send scheduled recommendation requests';
$string['thanksforrecommendation'] = 'Thank you, your recommendation has been processed.';
$string['timecompleted'] = 'Completed';
$string['timerequested'] = 'Requested';
$string['typelabel'] = 'Explanation text without input control';
$string['typeradio'] = 'Collection of radio buttons';
$string['typetextarea'] = 'Multiline text with editor';
$string['typetextfield'] = 'Single line text field';
$string['yourrecommendations'] = 'Your recommendations';
