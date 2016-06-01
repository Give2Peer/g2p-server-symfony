@rest
Feature: Tag items
  In order to help others to filter
  As a user
  I want to attach tags to items



Background:
  Given I am the registered user named Goutte



# This is a dummy scenario : there should always be tags in the database.
Scenario: List all tags when there's none
   When I request the tags
   Then the request should be accepted
    And there should be 0 tags in the response



Scenario: List all tags
  Given there is a tag named "dirty"
    And there is a tag named "wet"
    And there is a tag named "broken"
   When I request the tags
   Then the request should be accepted
    And there should be 3 tags in the response
    #And I dump the response
    And the response should include :
"""
tags:
  - name: broken
  - name: dirty
  - name: wet
"""
    And the response should not include :
"""
tags:
  - name: nope
"""



