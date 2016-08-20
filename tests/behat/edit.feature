@mod @mod_recommend
Feature: Creating and editing questions in a recommendation module
  In order to use this plugin
  As a teacher
  I need to be able to set up and edit recommendations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario: Create recommendation and edit questions
    # Teacher creates recommendation activity
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Recommendation request" to section "1" and I fill the form with:
      | Name | Test recommendation |
    And I follow "Test recommendation"
    And I follow "Edit questions"
    And I follow "add question"
    And I follow "Explanation text without input control"
    And I set the following fields to these values:
      | Contents | This is a label |
    And I press "Save changes"
    And I should see "This is a label"
    And I log out
