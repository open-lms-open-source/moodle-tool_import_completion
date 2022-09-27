Feature: Import completions via tool_import_completion

  Background:
    Given the following "courses" exist:
      | name     | shortname | idnumber |
      | Course 1 | Cor1      | C1       |
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
    And I am on import completion page
    When I upload "admin/tool/import_completion/tests/fixtures/completionupload.csv" file to "File" filemanager
    And I set the following fields to these values:
      | User Mapping   | username  |
      | Course mapping | shortname |
    And I press "Import Completion/Grades"
    And I press "Upload Completions"
    And I should see "Total Records Submitted: 1"
    And I should see "Total Records Uploaded: 1"