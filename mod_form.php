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
 * The main recommend configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_recommend_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('recommendname', 'recommend'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'recommendname', 'recommend');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of recommend settings, spreading all them into the fieldset.

        $mform->addElement('header', 'requestssettings',
                get_string('requestssettings', 'recommend'));
        $mform->setExpanded('requestssettings', true);

        $mform->addElement('text', 'maxrequests',
                get_string('maxrequests', 'recommend'), array('size' => '5'));
        $mform->addHelpButton('maxrequests', 'maxrequests', 'recommend');
        $mform->setType('maxrequests', PARAM_INT);
        $mform->addRule('maxrequests', null, 'numeric', null, 'client');
        $mform->setDefault('maxrequests', 5);

        // TODO strings.
        $mform->addElement('text', 'requesttemplatesubject',
                'Request e-mail subject', array('size' => '64'));
        $mform->setDefault('requesttemplatesubject',
                get_string('requesttemplatesubject', 'recommend'));
        $mform->setType('requesttemplatesubject', PARAM_NOTAGS);

        $mform->addElement('editor', 'requesttemplatebodyeditor',
                'Request e-mail template', [],
                ['enable_filemanagement' => false, 'maxfiles' => 0]);
        $mform->setDefault('requesttemplatebodyeditor',
                ['text' => text_to_html(get_string('requesttemplatebody', 'recommend')),
                    'format' => FORMAT_HTML]);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function add_completion_rules() {
        $mform = $this->_form;

        $group = array();
        $group[] = $mform->createElement('checkbox', 'completionrequired', '',
                get_string('completionrequired', 'recommend'));
        $group[] = $mform->createElement('text', 'requiredrecommend', '', ['size' => 5]);
        $group[] = $mform->createElement('checkbox', 'completiononlyaccepted',
                '', get_string('completiononlyaccepted', 'recommend'));
        $mform->addGroup($group, 'requiredrecommendgroup',
                get_string('requiredrecommendgroup', 'recommend'), array(' ', '<br>'), false);
        $mform->addHelpButton('requiredrecommendgroup', 'requiredrecommendgroup', 'recommend');
        $mform->disabledIf('requiredrecommend', 'completionrequired', 'notchecked');
        $mform->setType('requiredrecommend', PARAM_INT);
        //$mform->addRule('requiredrecommend', null, 'numeric', null, 'client');
        $mform->setDefault('requiredrecommend', 1);
        $mform->disabledIf('completiononlyaccepted', 'completionrequired', 'notchecked');

        return array('requiredrecommendgroup');
    }

    function completion_rule_enabled($data) {
        return !empty($data['completionrequired']) && $data['requiredrecommend'] > 0;
    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion setting if the checkbox is not ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionrequired) || !$autocompletion) {
                $data->requiredrecommend = 0;
            }
        }
        $data->completiononlyaccepted = empty($data->completiononlyaccepted) ? 0 : 1;
        return $data;
    }

    /**
     * Enforce defaults here
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        // Set up the completion checkbox which is not part of standard data.
        $defaultvalues['completionrequired'] =
            !empty($defaultvalues['requiredrecommend']) ? 1 : 0;
    }
}
