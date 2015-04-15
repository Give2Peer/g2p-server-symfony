@rest
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
    And there is an item at 43.578658, 1.468091
    And there is an item at 43.566591, 1.474969


Scenario: List 2 of 2 items around coordinates
   When I GET /find/43.579909/1.467469
   Then the request should be accepted
    And there should be 2 items in the response


Scenario: List the first 32 of 33 items around coordinates
  Given there are 31 items at 43.578658, 1.468091
   When I GET /find/43.579909/1.467469
   Then the request should be accepted
    And there should be 32 items in the response


Scenario: List after the first 32 of 33 items around coordinates
  Given there are 31 items at 43.578658, 1.468091
   When I GET /find/43.579909/1.467469/32
   Then the request should be accepted
    And there should be 1 item in the response


# Handy dumper for creating mock JSON
#Scenario: Dump fixtures items
#  Given I load the fixtures
#   When I GET /find/43.579909/1.467469
#   Then the request should be accepted
#    And I dump the response

