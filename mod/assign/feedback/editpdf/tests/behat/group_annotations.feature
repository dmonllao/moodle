@mod @mod_assign @assignfeedback @assignfeedback_editpdf @_file_upload
Feature: In a group assignment, teacher can annotate PDF files for all users
  In order to provide visual report on a graded PDF for all users
  As a teacher
  I need to use the PDF editor for a group assignment

  @javascript
  Scenario: Submit a PDF file ASD
    Given I change window size to "small"
    And I wait "3" seconds
    And I change window size to "medium"
    And I wait "3" seconds
    And I change window size to "2000x1000"
    And I wait "3" seconds
