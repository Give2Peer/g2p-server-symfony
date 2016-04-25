@rest
@karma
Feature: Gaining karma
  In order to level up
  As a gamer
  I want to gain karma


Background:
  Given I am the registered user named Goutte
    And there is an item at 42.0, 1.0



Scenario: Gain karma when adding an item



Scenario: Gain karma once a day just by looking at the map
  Then I should have 0 karma points
  When I request the items around 41.0, 2.0
  Then the request should be accepted
   And I should have 1 karma point
  When I request the items around 41.0, 2.0
  Then the request should be accepted
   And I should still have 1 karma point
  When I gained the daily karma 12 hours ago
   And I request the items around 41.0, 2.0
   But I should still have 1 karma point
  # It should be 24 hours, BUT the now() on the server and in the test suite
  # are not always the same... Wait... what ?
  # my remote server is out of sync, that I know. But this ? why ?
  When I gained the daily karma 36 hours ago
   And I request the items around 41.0, 2.0
  Then I should now have 2 karma points

