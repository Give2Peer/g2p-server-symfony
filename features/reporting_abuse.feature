@rest
@abuse
Feature: Reporting abuse
  In order to censor inappropriate content
  As a being
  I want to be able to report abuse

# Scenario: Fail to report abuse because you are not level ??? yet

# Scenario: Gain ability to report abuse at level ???

Background:
  Given there is a user named Inferior
    And there is a user named Equal
    And there is a user named Superior
    And there is a user named Abuser
    And Inferior is level 1
    And Equal is level 4
    And Superior is level 7
    And Abuser is level 4
    And Abuser added without karma gain an item titled "P0RN"


Scenario: Fail to report abuse when you have a lower karmic level than the author
  Given I am the user named Inferior
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then the request should be denied
    And I should still see 1 item


Scenario: Report abuse when you have a higher karmic level than the author
  Given I am the user named Superior
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then the request should be accepted
    And I should now see 0 items
