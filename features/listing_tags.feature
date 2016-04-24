@rest
Feature: Tag items
  In order to help others to filter
  As a user
  I want to attach tags to items


Background:
  Given I am the registered user named Goutte


Scenario: List all tags when there's none
   When I request the tags
   Then the request should be accepted
    And there should be 0 items in the response


Scenario: List all tags
  Given there is a tag named "dirty"
    And there is a tag named "wet"
    And there is a tag named "old"
   When I request the tags
   Then the request should be accepted
    And there should be 3 items in the response
    And I dump the response
    And the response should include :
"""
- dirty
- wet
- old
"""
    And the response should not include :
"""
- nope
- carrots
"""



