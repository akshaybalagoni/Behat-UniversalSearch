@set-config
Feature: Universal Search XML Feeds
  In order to deliver content to Universal Search providers
  The XML feeds for each provider must be valid and return without error

  Scenario: Apple Catalog feed should be valid
    Given I am at "/universal-search-feed?_format=apple_us_catalog"
    Then the response should contain "umcCatalog"
    And the response should not contain "XSD validation failed"

  Scenario: Apple Availability feed should be valid
    Given I am at "/universal-search-feed?_format=apple_us_availability"
    Then the response should contain "umcAvailability"
    And the response should not contain "XSD validation failed"