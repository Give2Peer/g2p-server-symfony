@rest
Feature: Providing statistics
  In order to brag about how kickass I am
  As a service
  I need to collect and provide statistics


Background:
  Given I am the registered user named Goutte


Scenario: Get initial statistics
   When I request the statistics
   Then the request should be accepted
    And the response should include :
"""
users_count: 1
items_count: 0
"""

@wip
Scenario: Get updated statistics
  Given I already gave 42 items
    And there is a user named "Sherlock"
   When I request the statistics
   Then the request should be accepted
    And the response should include :
"""
users_count: 2
items_count: 42
"""

# 3 leaderboards, in the end :
# - top (user may not be in it)
# - me (around user)
# - friends (user and friends -- requires friendship relations)
#leaderboards:
#    karma:
#        friends:
#        me:
#        top:
#            0:
#                username: goutte
