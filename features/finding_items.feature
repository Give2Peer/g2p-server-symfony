@rest
@find
Feature: Find items
  In order to use or recycle items
  As a gatherer
  I need to fetch the items in my vicinity


# I am there :
# 6 Rue de Fontainebleau, 31400 Toulouse
# 43.579909, 1.467469
# Distance: 0

# 111 Avenue de Lespinet, 31400 Toulouse
# 43.578658, 1.468091
# Distance: 148

# 10 Rond-Point Jean Lagasse, 31400 Toulouse
# 43.566591, 1.474969
# Distance : 1601


Background:
  Given I am the registered user named "Goutte"


Scenario: List 2 of 2 items around coordinates
  Given there is an item at 43.578658, 1.468091
    And there is an item at 43.566591, 1.474969
   When I get /items/around/43.579909/1.467469
   Then the request should be accepted
    And there should be 2 items in the response


Scenario: List the first 64 of 70 items around coordinates
  Given there are 70 items at 43.578658, 1.468091
   When I get /items/around/43.579909/1.467469
   Then the request should be accepted
    And there should only be 64 items in the response


Scenario: List after the first 64 of 70 items around coordinates
  Given there are 70 items at 43.578658, 1.468091
   When I get /items/around/43.579909/1.467469 with the parameters :
"""
skip: 64
"""
   Then the request should be accepted
    And there should only be 6 items in the response


Scenario: Skip items further than a provided distance in meters
  Given there are 5 items at 43.578658, 1.468091
    And there are 3 items at 03.578658, 20.48091
   When I get /items/around/43.579909/1.467469 with the parameters :
"""
maxDistance: 1000
"""
   Then the request should be accepted
    And there should only be 5 items in the response


# Handy dumper for creating mock JSON
#Scenario: Dump fixtures items
#  Given I load the fixtures
#   When I GET /find/43.579909/1.467469
#   Then the request should be accepted
#    And I dump the response

