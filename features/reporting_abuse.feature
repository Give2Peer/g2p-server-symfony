@rest
@abuse
Feature: Reporting abuse
  In order to censor inappropriate content
  As a being
  I want to be able to report abuse


Background:
  Given there is a user named Abuser of level 4
    And Abuser added without karma gain an item titled "P0RN"
    And there is a user named Maybe Abuser's Bot of level 0
    And there is a user named Possibly Not A Bot of level 1
    And there is a user named Inferior Delator A of level 3
    And there is a user named Inferior Delator B of level 3
    And there is a user named Inferior Delator C of level 3
    And there is a user named Superior Delator   of level 7


Scenario: Fail to report abuse when you are level 0
  Given I am the user named Maybe Abuser's Bot
   When I try to report the item titled "P0RN" as abusive
   Then my request should be denied


Scenario: Gain ability to report abuse at level 1
  Given I am the user named Possibly Not A Bot
   When I try to report the item titled "P0RN" as abusive
   Then my request should be accepted


Scenario: Fail to report abuse when you're the author
  Given I am the user named Abuser
   Then I should see 1 item
   When I try to report the item titled "P0RN" as abusive
   Then my request should be denied
    And I should still see 1 item


Scenario: Fail to report abuse twice for the same item
  Given I am the user named Inferior Delator A
   Then I should see 1 item
   When I try to report the item titled "P0RN" as abusive
   Then my request should be accepted
    But I should still see 1 item
   When I try to report the item titled "P0RN" as abusive
   Then my request should be denied
    And I should still see 1 item


Scenario: Item is deleted when multiple low-karma reports are made
        # and the summed karmic will against the item exceeds the author's golden karma
  Given I am the user named Inferior Delator A
   Then I should see 1 item
   When I try to report the item titled "P0RN" as abusive
   Then my request should be accepted
    But I should still see 1 item
   When I am the user named Inferior Delator B
    And I try to report the item titled "P0RN" as abusive
   Then my request should be accepted
    But I should still see 1 item
   When I am the user named Inferior Delator C
    And I try to report the item titled "P0RN" as abusive
   Then my request should be accepted
    And I should now see 0 item


Scenario: Item is deleted when you have waaay more karma than the author
  Given I am the user named Superior Delator
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then my request should be accepted
    And I should now see 0 items


Scenario: Cancel your report and the item deletion
  Given I am the user named Superior Delator
   Then I should see 1 item
   When I report the item titled "P0RN" as abusive
   Then my request should be accepted
    And I should now see 0 items
   When I cancel my report on the item titled "P0RN"
   Then my request should be accepted
    And I should now see 1 item


