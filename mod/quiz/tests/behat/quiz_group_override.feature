@mod @mod_quiz
Feature: Quiz group override in separate groups mode
  In order to override groups
  As a teacher
  I need to see only groups I'm a member of myself

  Background:
    Given I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | moodle/site:accessallgroups | Inherit |
    And I log out
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | teacher1 | Terry 1    | Teacher 1 | teacher1@example.com |
      | student1 | Sam 1      | Student 1 | student1@example.com |
      | teacher2 | Terry 2    | Teacher 2 | teacher2@example.com |
      | student2 | Sam 2      | Student 2 | student2@example.com |
      | teacher3 | Terry 3    | Teacher 3 | teacher3@example.com |
      | student3 | Sam 3      | Student 3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher2 | C1     | editingteacher |
      | student2 | C1     | student        |
      | teacher3 | C1     | editingteacher |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
      | Group 3 | C1     | G3       |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | teacher1 | G1 |
      | teacher1 | G3 |
      | student2 | G2 |
      | teacher2 | G2 |
      | teacher2 | G3 |
      | student3 | G3 |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And the following "activities" exist:
      | activity | name           | intro                 | course | idnumber | groupmode |
      | quiz     | Test quiz name | Test quiz description | C1     | quiz1    | 1         |
    And quiz "Test quiz name" contains the following questions:
      | question | page |
      | TF1      | 1    |

  @javascript
  Scenario: Override Group 1 as teacher of Group 1
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And the "Override group" select box should contain "Group 1"
    And the "Override group" select box should not contain "Group 2"

  Scenario: Override Student 1 as teacher of Group 1
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "User overrides" in current page administration
    And I press "Add user override"
    And the "Override user" select box should contain "Sam 1 Student 1, student1@example.com"
    And the "Override user" select box should not contain "Sam 2 Student 2, student2@example.com"

  Scenario: Override Group 1 as teacher in no group
    And I log in as "teacher3"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Group overrides" in current page administration
    And I should see "There are either no groups in this course or no groups you are allowed to see"
    Then the "Add group override" "button" should be disabled