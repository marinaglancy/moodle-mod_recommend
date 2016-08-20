@mod @mod_recommend
Feature: Requesting, filling and accepting recommendation
  In order to use this plugin
  As a user
  I need to request recommendations

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
    And the following "activities" exist:
      | activity   | name                | course | idnumber   |
      | recommend  | Test recommendation | C1     | recommend1 |
    And recommendation module "Test recommendation" contains the following questions:
      | type | question | addinfo |
      | label | welcome | |
      | radio | Analytic abilities | 1/Below average\n2/Average\n3/Good\n4/Excellent |
      | textarea | Write essay | |
      | textfield | your phone | |
      | textfield | your name | name |
      | textfield | E-mail address | email |

  Scenario: Recommendation request workflow
    # Teacher creates recommendation activity
    #When I log in as "teacher1"
    #And I follow "Course 1"
    #And I turn editing mode on
    #And I add a "Recommendation request" to section "1" and I fill the form with:
    #  | Name | Test recommendation |
    #And I log out
    # Student sends recommendation requests
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test recommendation"
    And I follow "Request recommendation"
    And I set the following fields to these values:
      | name1 | Testperson1 |
      | email1 | recommendator1@someaddress.invalid |
      | name2 | Testperson2 |
      | email2 | recommendator2@someaddress.invalid |
    And I press "Save changes"
    And I should see "Your recommendations"
    And "Scheduled" "text" should exist in the "Testperson1" "table_row"
    And I click on "Delete" "link" in the "Testperson2" "table_row"
    And I wait to be redirected
    Then I should see "Your recommendations"
    And I should see "Testperson1"
    And I should not see "Testperson2"
    And I log out
    # Filling recommendation
    And I open the recommendation as "Testperson1"
    And I should see "Recommendation for Student 1"
    And I should see "welcome"
    And the field "your name" matches value "Testperson1"
    And the field "E-mail address" matches value "recommendator1@someaddress.invalid"
    And I set the following fields to these values:
      | Analytic abilities Good | 1 |
      | Write essay | I had a good experience working with this person |
      | your name | The Big Boss |
      | your phone | 0123456789 |
    And I press "Save changes"
    And I should see "Recommendation for Student 1"
    And I should see "Thank you, your recommendation has been processed."
    And I am on homepage
    # Check that the student sees the recommendation as completed
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test recommendation"
    And I should see "Your recommendations"
    And "Recommendation completed" "text" should exist in the "Testperson1" "table_row"
    And I log out
    # Accept recommendation as a teacher
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test recommendation"
    And I click on "Recommendation completed" "link" in the "//table//tr[contains(.,'Student 1')]/td[contains(@class,'c1')]" "xpath_element"
    And I should see "Name of the recommending person: Testperson1"
    And I should see "Student 1"
    And I should see "Status:  Recommendation completed"
    And I should see "welcome"
    And I should see "[X] Good"
    And I should see "I had a good experience working with this person"
    And I should see "The Big Boss"
    And I should see "0123456789"
    And I should see "recommendator1@someaddress.invalid"
    And I click on "Accept" "link" in the "#region-main" "css_element"
    And I wait to be redirected
    And I should see "Status:  Recommendation accepted"
    And I log out
    # Check that student can also see it as accepted
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test recommendation"
    And I should see "Your recommendations"
    And "Recommendation accepted" "text" should exist in the "Testperson1" "table_row"
    And I log out
