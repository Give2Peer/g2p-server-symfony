@rest
@wip
Feature: Tag items
  In order to
  As a
  I need to

Background:
  Given I am the registered user named "Goutte"
#    And there is an item at 43.578658, 1.468091
#    And there is an item at 43.566591, 1.474969


# see https://github.com/Behat/Behat/issues/726
Scenario: Dummy scenario to skip behat's buggy behavior with first scenario
   When I do nothing
   Then nothing happens


Scenario: List all tags when there's none
   When I GET /tags
   Then the request should be accepted
    And there should be 0 items in the response


Scenario: List all tags
  Given there is a tag named "book"
    And there is a tag named "wet"
    And there is a tag named "old"
   When I GET /tags
   Then the request should be accepted
    And there should be 3 items in the response
    And I dump the response
    And the response should include :
"""
- book
- wet
- old
"""
    And the response should not include :
"""
- nope
- carrots
"""



