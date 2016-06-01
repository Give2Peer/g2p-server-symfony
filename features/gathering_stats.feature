@rest
@stats
Feature: Providing statistics
  In order to brag about how used I am
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
items_total: 0
"""



Scenario: Get updated statistics
  Given I gave an item titled "mistake"
    And I already gave 42 items
    And I deleted the item titled "mistake"
    And there is a user named Sherlock
   When I request the statistics
   Then the request should be accepted
    And I dump the response
    And the response should include :
"""
users_count: 2
items_count: 42
items_total: 43
"""



# 3 leaderboards, in the end, like awesomenauuuuuuuts :
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
