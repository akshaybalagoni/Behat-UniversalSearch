@javascript @api
Feature: Universal Search Settings
  In order to manage Universal Search Integration
  As an authenticated user
  I need to be able to set Universal Search Settings

  Scenario: Deep link protocol is required
    Given I am logged in as a user with the "administrator" role
    And I open the Draco Universal Search Settings form
    And I disable HTML 5 required validation on the "Deep link protocol" field
    And I press the "Save configuration" button
    Then I should see the following error message:
      | error messages |
      | Deep link protocol field is required |
    And I should not see the following error messages:
      | error messages |
      | The ODT destination is required |
      | The Apple Team ID is required |
      | The Apple Service ID is required |
      | The Apple Catalog ID is required |

  Scenario: Set the generic parameters
    Given I am logged in as a user with the "administrator" role
    And I open the Draco Universal Search Settings form
    And I fill in "Feed title" with "My super rad feed"
    And I fill in "Feed description" with "I'd like to describe this, but if I did, it would blow up your mind."
    And I fill in "Deep link protocol" with "watchtbs"
    And I press the "Save configuration" button
    Then I should see the success message "The configuration options have been saved."
    And the "Feed title" field should contain "My super rad feed"
    And the "Feed description" field should contain "I'd like to describe this, but if I did, it would blow up your mind."
    And the "Deep link protocol" field should contain "watchtbs"
    And the configuration item "draco_universal_search.settings" with key "title" should be "My super rad feed"
    And the configuration item "draco_universal_search.settings" with key "description" should be "I'd like to describe this, but if I did, it would blow up your mind."
    And the configuration item "draco_universal_search.settings" with key "deep_link_protocol" should be "watchtbs"

  Scenario: Apple specific parameters are required when Apple feed is toggled on
    Given I am logged in as a user with the "administrator" role
    And I open the Draco Universal Search Settings form
    And I check the box "Enable Apple Universal Search feeds"
    And I disable HTML 5 required validation on the fields:
      | field |
      | ODT destination |
      | Team ID |
      | Service ID |
      | Catalog ID |
    And I press the "Save configuration" button
    Then I should see the following error messages:
      | error messages |
      | The Apple Team ID is required |
      | The Apple Service ID is required |
      | The Apple Catalog ID is required |

  Scenario: Set the Apple specific parameters
    Given I am logged in as a user with the "administrator" role
    And I open the Draco Universal Search Settings form
    And I check the box "Enable Apple Universal Search feeds"
    And I select "APPLE" from "ODT destination"
    And I fill in "Team ID" with "team_id"
    And I fill in "Service ID" with "service_id"
    And I fill in "Catalog ID" with "catalog_id"
    And I select "Portuguese, Portugal" from "default_locale"
    And I press the "Save configuration" button
    Then I should see the success message "The configuration options have been saved."
    And the "ODT destination" field should contain "APPLE"
    And the "Team ID" field should contain "team_id"
    And the "Catalog ID" field should contain "catalog_id"
    And the "Service ID" field should contain "service_id"
    And the "Default locale" field should contain "pt-pt"
    And the configuration item "draco_universal_search.settings" with key "apple_odt_destination" should be "APPLE"
    And the configuration item "draco_universal_search.settings" with key "team_id" should be "team_id"
    And the configuration item "draco_universal_search.settings" with key "service_id" should be "service_id"
    And the configuration item "draco_universal_search.settings" with key "catalog_id" should be "catalog_id"
    And the configuration item "draco_universal_search.settings" with key "default_locale" should be "pt-pt"