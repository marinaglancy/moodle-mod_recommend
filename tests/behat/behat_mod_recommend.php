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
 * Steps definitions related to mod_recommend
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related to mod_quiz.
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_recommend extends behat_base {

    /**
     * Generates the question in a recommendation module instance
     *
     * @param string $recommendname
     * @param TableNode $data information about the questions to add.
     *
     * @Given /^recommendation module "([^"]*)" contains the following questions:$/
     */
    public function recommendation_contains_the_following_questions($recommendname, TableNode $data) {
        global $DB;

        $recommend = $DB->get_record('recommend', ['name' => $recommendname], '*', MUST_EXIST);

        // Add the questions.
        $sortorder = 1;
        foreach ($data->getHash() as $tabledata) {
            $data = ['sortorder' => $sortorder++, 'recommendid' => $recommend->id];
            foreach ($tabledata as $key => $value) {
                $data[$key] = preg_replace('/\\\\n/', "\n", $value);
            }
            $DB->insert_record('recommend_question', $data);
        }
    }

    /**
     * Open a recommendation without login
     * @param string $name the name or email of the person who received recommendation request
     *
     * @Given /^I open the recommendation as "([^"]*)"$/
     */
    public function i_open_the_recommendation_as($name) {
        global $DB;
        $record = $DB->get_record_sql('SELECT * '
                . 'FROM {recommend_request} '
                . 'WHERE name = ? OR email = ?', [$name, $name], 'secret', MUST_EXIST);
        $this->getSession()->visit($this->locate_path('/mod/recommend/recommend.php?s='.$record->secret));
    }

}
