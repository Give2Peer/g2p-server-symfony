@rest
Feature: List items
  In order to find stuff I could use or recycle
  As a user
  I need to list items in my vicinity

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


# see https://github.com/Behat/Behat/issues/726
Scenario: Dummy scenario to skip behat's buggy behavior with first scenario
   When I do nothing
   Then nothing happens


Scenario: List items around coordinates
   When I GET /list/43.579909/1.467469
   Then the request should be accepted
    And there should be 2 items in the response

