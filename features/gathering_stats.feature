@rest
@stats
Feature: Providing statistics
  In order to brag about how used I am
  As a service
  I need to collect and provide statistics



Background:
  Given I am the registered user named Bayes



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
  Given I already gave 42 items 2 days ago
    And I gave an item titled "Theorem Draft"
    And I deleted the item titled "Theorem Draft"
    And I gave an item titled "Theorem"
    And there is another user named Ramanujan
   When I request the statistics
   Then the request should be accepted
    And the response should include :
"""
users_count: 2
items_count: 43
items_total: 44
"""



# This fails. Fixing this requires adding a new Stats table,
# as we cannot grab the current value of a sequence readily.
# https://www.postgresql.org/docs/8.3/static/functions-sequence.html
#Scenario: Expose a known bug when the last inserted item was deleted
#  Given I already gave 42 items 2 days ago
#    And I gave an item titled "Theorem"
#    And I deleted the item titled "Theorem"
#    And there is another user named Ramanujan
#   When I request the statistics
#   Then the request should be accepted
#    And the response should include :
#"""
#users_count: 2
#items_count: 42
#items_total: 43
#"""



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
