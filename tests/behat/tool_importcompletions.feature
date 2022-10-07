Feature: Import completions via tool_import_completion

  Background:
    Given the following "courses" exist:
      | fullname     | shortname | idnumber | enablecompletion |
      | Course 1     | Cor1      | C1       | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | student1 | Student   | 1        | student1@example.com | s1       |
      | student2 | Student   | 2        | student2@example.com | s2       |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | Cor1   | student |

  @javascript @_file_upload
  Scenario: Upload course completions via tool_import_completion
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Manual completion by others" "link"
    And I set the field "Teacher" to "checked"
    And I press "Save changes"
    And I am on import completion page
    When I upload "admin/tool/import_completion/tests/fixtures/completionupload.csv" file to "File" filemanager
    And I set the following fields to these values:
      | User Mapping   | email     |
      | Course mapping | shortname |
    And I press "Import Completion/Grades"
    And I press "Upload Completions"
    And I should see "Total Records Submitted: 1"
    And I should see "Total Records Uploaded: 1"
    And I am on "Course 1" course homepage
    And I add the "Course completion status" block
    And I click on "View course report" "link" in the "Course completion status" "block"
    And I should see "Student 1" in the "completion-progress" "table"
    And "//img[@alt='Student 1, Course complete: Completed 11/02/22, 00:00']" "xpath_element" should exist
