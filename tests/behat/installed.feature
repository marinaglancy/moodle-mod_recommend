@mod @mod_recommend
Feature: Installation of mod_recommend succeeds
  In order to use this plugin
  As a user
  I need the installation to work

  Scenario: Check the Plugins overview for the name of the mod_recommend plugin
    Given I log in as "admin"
    And I navigate to "Plugins overview" node in "Site administration > Plugins"
    Then the following should exist in the "plugins-control-panel" table:
      |Plugin name  |
      |mod_recommend|
