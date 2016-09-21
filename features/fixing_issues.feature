@bug
Feature: Fixing Issues
  In order to definitely fix issues
  As a developer
  I want to describe scenarios that failed in the past


Scenario: Cascade delete reports when the related item is deleted
  Given I am the user named Goutte of level 1
    And there is a user named Kiouze of level 5
    And Kiouze added without karma gain an item titled "SGB"
    And I report the item titled "SGB" as abusive
   Then my request should be accepted
   When the item titled "SGB" is hard deleted
   Then the report of Goutte on the item titled "SGB" should be deleted too
