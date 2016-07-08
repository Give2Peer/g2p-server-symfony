@rest
@abuse
Feature: Reporting abuse
  In order to censor inappropriate content
  As a being
  I want to be able to report abuse

# Scenario: Fail to report abuse because you are not level ??? yet

# Scenario: Gain ability to report abuse at level ???

Background:
  Given there is a user named Inferior of level 1
    And there is a user named Equal    of level 4
    And there is a user named Superior of level 7
    And there is a user named Abuser   of level 4
    And Abuser added without karma gain an item titled "P0RN"


Scenario: Fail to report abuse when you have less karma than the author
  Given I am the user named Inferior
   Then I should see 1 item
   When I try to report the item titled "P0RN" as abusive
   Then my request should be denied
    And I should still see 1 item


Scenario: Report abuse when you have the same karma as the author
  Given I am the user named Equal
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then my request should be accepted
    And I should now see 0 items


Scenario: Report abuse when you have more karma than the author
  Given I am the user named Superior
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then my request should be accepted
    And I should now see 0 items
