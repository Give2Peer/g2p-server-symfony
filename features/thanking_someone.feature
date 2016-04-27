@rest
@karma
Feature: Thanking someone
  In order to be healthy
  As a being
  I want to be thankful

# Scenario: Fail to thank someone because you are not level ??? yet

# Scenario: Gain ability to thank other users for their items at level ???

Scenario: Thank someone when you are level 13
  Given I am the registered user named Antoine
    And I am level 13
    And there is a user named Eva
    And Eva added without karma gain an item titled "Friendship"
   When I thank the author of the item titled "Friendship"
   Then the request should be accepted
    And Eva should have 14 karma points
